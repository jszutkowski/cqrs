<?php

declare(strict_types=1);

namespace App\Loyalty\Infrastructure\ReadModel;

use App\Loyalty\Application\ReadModel\PointsEntry;
use App\Loyalty\Application\ReadModel\WalletDetails;
use App\Loyalty\Application\ReadModel\WalletReadModel;
use App\Loyalty\Application\ReadModel\WalletSummary;
use App\Shared\Infrastructure\Dbal\Row;
use Doctrine\DBAL\Connection;

final readonly class DbalWalletReadModel implements WalletReadModel
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function listWallets(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, balance FROM wallets ORDER BY id ASC',
        );

        return array_map(
            static function (array $row): WalletSummary {
                $row = Row::of($row);

                return new WalletSummary($row->string('id'), $row->int('balance'));
            },
            $rows,
        );
    }

    public function findWallet(string $walletId): ?WalletDetails
    {
        $wallet = $this->connection->fetchAssociative(
            'SELECT id, balance FROM wallets WHERE id = :walletId',
            ['walletId' => $walletId],
        );

        if (false === $wallet) {
            return null;
        }

        $wallet = Row::of($wallet);

        $rows = $this->connection->fetchAllAssociative(
            'SELECT amount, transfer_id, registered_at
             FROM wallet_points
             WHERE wallet_id = :walletId
             ORDER BY registered_at DESC, id DESC',
            ['walletId' => $walletId],
        );

        $history = array_map(
            static function (array $row): PointsEntry {
                $row = Row::of($row);

                return new PointsEntry(
                    $row->int('amount'),
                    new \DateTimeImmutable($row->string('registered_at')),
                    $row->nullableString('transfer_id'),
                );
            },
            $rows,
        );

        return new WalletDetails($wallet->string('id'), $wallet->int('balance'), $history);
    }
}
