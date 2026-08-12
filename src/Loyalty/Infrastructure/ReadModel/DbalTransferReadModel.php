<?php

declare(strict_types=1);

namespace App\Loyalty\Infrastructure\ReadModel;

use App\Loyalty\Application\ReadModel\TransferReadModel;
use App\Loyalty\Application\ReadModel\TransferSummary;
use App\Loyalty\Domain\Transfer\TransferStatus;
use App\Shared\Infrastructure\Dbal\Row;
use Doctrine\DBAL\Connection;

final readonly class DbalTransferReadModel implements TransferReadModel
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function findTransfer(string $transferId): ?TransferSummary
    {
        $row = $this->connection->fetchAssociative(
            'SELECT id, source_wallet_id, target_wallet_id, points, status, failure_reason
             FROM transfers
             WHERE id = :transferId',
            ['transferId' => $transferId],
        );

        if (false === $row) {
            return null;
        }

        $row = Row::of($row);

        return new TransferSummary(
            $row->string('id'),
            $row->string('source_wallet_id'),
            $row->string('target_wallet_id'),
            $row->int('points'),
            TransferStatus::from($row->string('status')),
            $row->nullableString('failure_reason'),
        );
    }
}
