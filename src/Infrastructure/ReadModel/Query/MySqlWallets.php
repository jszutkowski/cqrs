<?php

declare(strict_types=1);

namespace App\Infrastructure\ReadModel\Query;

use App\Application\ReadModel\Query\Wallets as WalletsInterface;
use App\Application\ReadModel\View\FullWalletView\Points;
use App\Application\ReadModel\View\FullWalletView\Wallet;
use Doctrine\DBAL\Connection;

class MySqlWallets implements WalletsInterface
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return array<string, int>
     *
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function getWallets(): array
    {
        $statement = $this->connection->executeQuery('SELECT id, balance FROM wallets');

        $result = [];
        foreach ($statement->fetchAllAssociative() as $row) {
            $result[(string) $row['id']] = (int) $row['balance'];
        }

        return $result;
    }

    public function getWallet(string $id): ?Wallet
    {
        $statement = $this->connection->executeQuery(<<<SQL
            SELECT w.id as wallet_id, w.balance, p.amount, p.created_at
            FROM wallets w
            LEFT JOIN points p ON p.wallet_id = w.id
            WHERE w.id = :walletId
            ORDER BY p.created_at DESC
        SQL, ['walletId' => $id]);

        $wallet = null;
        foreach ($statement->fetchAllAssociative() as $row) {
            if (null === $wallet) {
                $wallet = new Wallet($row['wallet_id'], (int) $row['balance']);
            }

            if (null !== $row['amount']) {
                $points = new Points(
                    (int) $row['amount'],
                    \DateTime::createFromFormat('Y-m-d H:i:s', $row['created_at'])->format('Y-m-d H:i:s')
                );

                $wallet->points[] = $points;
            }
        }

        return $wallet;
    }
}
