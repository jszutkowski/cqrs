<?php

declare(strict_types=1);

namespace App\Loyalty\Infrastructure\Projector;

use App\Loyalty\Application\Notification\NotificationChannel;
use App\Loyalty\Application\Notification\NotificationName;
use App\Loyalty\Domain\Wallet\Event\PointsAdded;
use App\Loyalty\Domain\Wallet\Event\PointsRefunded;
use App\Loyalty\Domain\Wallet\Event\PointsWithdrawn;
use App\Loyalty\Domain\Wallet\Event\WalletCreated;
use App\Shared\Application\Bus\MessageBus;
use App\Shared\Application\Notification\Notification;
use App\Shared\Application\Notification\Notifier;
use App\Shared\Domain\EventSourcing\DomainEvent;
use App\Shared\Infrastructure\Dbal\Row;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Builds the wallet read model from the event stream.
 *
 * It also pushes the live update, rather than leaving that to a second handler
 * on the same event: the balance a client is told about must be the balance
 * this projector just wrote, and Messenger gives no ordering guarantee between
 * independent handlers of one message.
 */
final readonly class WalletProjector
{
    public function __construct(
        private Connection $connection,
        private Notifier $notifier,
    ) {
    }

    #[AsMessageHandler(bus: MessageBus::EVENT)]
    public function projectWalletCreated(WalletCreated $event): void
    {
        $this->connection->executeStatement(
            'INSERT INTO wallets (id, balance) VALUES (:walletId, 0) ON DUPLICATE KEY UPDATE id = id',
            ['walletId' => $event->aggregateId],
        );

        $this->notifier->notify(new Notification(
            NotificationChannel::Wallets->value,
            NotificationName::WalletCreated->value,
            ['walletId' => $event->aggregateId, 'balance' => 0],
        ));
    }

    #[AsMessageHandler(bus: MessageBus::EVENT)]
    public function projectPointsAdded(PointsAdded $event): void
    {
        $this->applyBalanceChange($event, $event->points, $event->transferId);
    }

    #[AsMessageHandler(bus: MessageBus::EVENT)]
    public function projectPointsWithdrawn(PointsWithdrawn $event): void
    {
        $this->applyBalanceChange($event, -$event->points, $event->transferId);
    }

    #[AsMessageHandler(bus: MessageBus::EVENT)]
    public function projectPointsRefunded(PointsRefunded $event): void
    {
        $this->applyBalanceChange($event, $event->points, $event->transferId);
    }

    private function applyBalanceChange(DomainEvent $event, int $delta, ?string $transferId): void
    {
        $this->connection->executeStatement(
            'INSERT INTO wallet_points (wallet_id, amount, transfer_id, registered_at)
             VALUES (:walletId, :amount, :transferId, :registeredAt)',
            [
                'walletId' => $event->aggregateId,
                'amount' => $delta,
                'transferId' => $transferId,
                'registeredAt' => $event->occurredAt->format('Y-m-d H:i:s.u'),
            ],
        );

        $this->connection->executeStatement(
            'UPDATE wallets SET balance = balance + :amount WHERE id = :walletId',
            ['amount' => $delta, 'walletId' => $event->aggregateId],
        );

        $balance = Row::of(['balance' => $this->connection->fetchOne(
            'SELECT balance FROM wallets WHERE id = :walletId',
            ['walletId' => $event->aggregateId],
        )])->int('balance');

        $payload = [
            'walletId' => $event->aggregateId,
            'amount' => $delta,
            'balance' => $balance,
            'transferId' => $transferId,
            'registeredAt' => $event->occurredAt->format(\DateTimeInterface::ATOM),
        ];

        $this->notifier->notify(new Notification(
            NotificationChannel::Wallet->forWallet($event->aggregateId),
            NotificationName::BalanceChanged->value,
            $payload,
        ));

        $this->notifier->notify(new Notification(
            NotificationChannel::Wallets->value,
            NotificationName::BalanceChanged->value,
            $payload,
        ));
    }
}
