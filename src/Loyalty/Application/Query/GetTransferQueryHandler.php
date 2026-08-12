<?php

declare(strict_types=1);

namespace App\Loyalty\Application\Query;

use App\Loyalty\Application\Exception\TransferDoesNotExist;
use App\Loyalty\Application\ReadModel\TransferReadModel;
use App\Loyalty\Application\ReadModel\TransferSummary;

final readonly class GetTransferQueryHandler
{
    public function __construct(
        private TransferReadModel $transferReadModel,
    ) {
    }

    /**
     * @throws TransferDoesNotExist
     */
    public function __invoke(GetTransferQuery $query): TransferSummary
    {
        return $this->transferReadModel->findTransfer($query->transferId)
            ?? throw TransferDoesNotExist::withId($query->transferId);
    }
}
