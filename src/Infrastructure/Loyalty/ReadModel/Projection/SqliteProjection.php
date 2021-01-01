<?php
declare(strict_types=1);

namespace App\Infrastructure\Loyalty\ReadModel\Projection;


use App\Domain\Loyalty\Events\PointsAdded;
use App\Domain\Loyalty\Events\PointsWithdrawn;
use App\Domain\Loyalty\Events\WalletCreated;
use Doctrine\DBAL\Connection;

class SqliteProjection extends AbstractProjection
{
    /**
     * @var Connection
     */
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function applyWalletCreated(WalletCreated $event): void
    {
        $this->connection->executeStatement('INSERT INTO wallets(id) VALUES (:walletId)', ['walletId' => $event->getId()]);
    }

    public function applyPointsAdded(PointsAdded $event): void
    {
        $this->connection->beginTransaction();

        $this->connection->executeStatement(
            'INSERT INTO points (amount, created_at, wallet_id) VALUES (:amount, :createdAt, :walletId)',
            [
                'amount' => $event->getPoints(),
                'createdAt' => $event->getCreatedAt()->format('U'),
                'walletId' => $event->getId()
            ]
        );

        $this->connection->executeStatement(
            'UPDATE wallets SET points = points + :points WHERE id = :id',
            [
                'points' => $event->getPoints(),
                'id' => $event->getId()
            ]
        );

        $this->connection->commit();
    }

    public function applyPointsWithdrawn(PointsWithdrawn $event): void
    {
        $this->connection->beginTransaction();

        $this->connection->executeStatement(
            'INSERT INTO points (amount, created_at, wallet_id) VALUES (:amount, :createdAt, :walletId)',
            [
                'amount' => -$event->getPoints(),
                'createdAt' => $event->getCreatedAt()->format('U'),
                'walletId' => $event->getId()
            ]
        );

        $this->connection->executeStatement(
            'UPDATE wallets SET points = points - :points WHERE id = :id',
            [
                'points' => $event->getPoints(),
                'id' => $event->getId()
            ]
        );

        $this->connection->commit();
    }
}
