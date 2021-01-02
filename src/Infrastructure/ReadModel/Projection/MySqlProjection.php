<?php

declare(strict_types=1);

namespace App\Infrastructure\ReadModel\Projection;

use App\Application\ReadModel\Projection\ProjectionInterface;
use App\Domain\EventSourcing\DomainEventApplierTrait;
use App\Domain\EventSourcing\DomainEventsStream;
use App\Domain\Loyalty\Events\PointsAdded;
use App\Domain\Loyalty\Events\WalletCreated;
use App\Infrastructure\EventListener\ProjectionUpdated;
use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MySqlProjection implements ProjectionInterface
{
    use DomainEventApplierTrait;

    private Connection $connection;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(Connection $connection, EventDispatcherInterface $eventDispatcher)
    {
        $this->connection = $connection;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __invoke(DomainEventsStream $events): void
    {
        $this->connection->beginTransaction();

        $this->apply($events);

        $this->connection->commit();

        $this->eventDispatcher->dispatch(new ProjectionUpdated($events), ProjectionUpdated::EVENT_NAME);
    }

    private function applyWalletCreated(WalletCreated $event): void
    {
        $this->connection->executeStatement('INSERT INTO wallets(id) VALUES (:walletId)', ['walletId' => $event->getAggregateId()]);
    }

    private function applyPointsAdded(PointsAdded $event): void
    {
        $this->connection->executeStatement(
            'INSERT INTO points (amount, created_at, wallet_id) VALUES (:amount, :createdAt, :walletId)',
            [
                'amount' => $event->getPoints()->getAmount(),
                'createdAt' => $event->getCreatedAt()->format('Y-m-d H:i:s'),
                'walletId' => $event->getAggregateId(),
            ]
        );

        $this->connection->executeStatement(
            'UPDATE wallets SET balance = balance + :amount WHERE id = :id',
            [
                'amount' => $event->getPoints()->getAmount(),
                'id' => $event->getAggregateId(),
            ]
        );
    }
}
