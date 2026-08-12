<?php

declare(strict_types=1);

namespace App\Loyalty\Infrastructure\Projector;

use App\Loyalty\Application\Notification\NotificationChannel;
use App\Loyalty\Application\Notification\NotificationName;
use App\Loyalty\Domain\Transfer\Event\TransferCompleted;
use App\Loyalty\Domain\Transfer\Event\TransferFailed;
use App\Loyalty\Domain\Transfer\Event\TransferInitiated;
use App\Loyalty\Domain\Transfer\TransferStatus;
use App\Shared\Application\Bus\MessageBus;
use App\Shared\Application\Notification\Notification;
use App\Shared\Application\Notification\Notifier;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

final readonly class TransferProjector
{
    public function __construct(
        private Connection $connection,
        private Notifier $notifier,
    ) {
    }

    #[AsMessageHandler(bus: MessageBus::EVENT)]
    public function projectTransferInitiated(TransferInitiated $event): void
    {
        $this->connection->executeStatement(
            'INSERT INTO transfers (id, source_wallet_id, target_wallet_id, points, status, initiated_at)
             VALUES (:transferId, :sourceWalletId, :targetWalletId, :points, :status, :initiatedAt)
             ON DUPLICATE KEY UPDATE id = id',
            [
                'transferId' => $event->aggregateId,
                'sourceWalletId' => $event->sourceWalletId,
                'targetWalletId' => $event->targetWalletId,
                'points' => $event->points,
                'status' => TransferStatus::Initiated->value,
                'initiatedAt' => $event->occurredAt->format('Y-m-d H:i:s.u'),
            ],
        );
    }

    #[AsMessageHandler(bus: MessageBus::EVENT)]
    public function projectTransferCompleted(TransferCompleted $event): void
    {
        $this->settle($event->aggregateId, TransferStatus::Completed, null);
    }

    #[AsMessageHandler(bus: MessageBus::EVENT)]
    public function projectTransferFailed(TransferFailed $event): void
    {
        $this->settle($event->aggregateId, TransferStatus::Failed, $event->reason);
    }

    private function settle(string $transferId, TransferStatus $status, ?string $failureReason): void
    {
        $this->connection->executeStatement(
            'UPDATE transfers SET status = :status, failure_reason = :failureReason WHERE id = :transferId',
            [
                'status' => $status->value,
                'failureReason' => $failureReason,
                'transferId' => $transferId,
            ],
        );

        $this->notifier->notify(new Notification(
            NotificationChannel::Wallets->value,
            NotificationName::TransferSettled->value,
            [
                'transferId' => $transferId,
                'status' => $status->value,
                'failureReason' => $failureReason,
            ],
        ));
    }
}
