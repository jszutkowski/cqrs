<?php

declare(strict_types=1);

namespace App\Loyalty\Application\Query;

use App\Loyalty\Application\Exception\WalletDoesNotExist;
use App\Loyalty\Application\ReadModel\WalletDetails;
use App\Loyalty\Application\ReadModel\WalletReadModel;

final readonly class GetWalletQueryHandler
{
    public function __construct(
        private WalletReadModel $walletReadModel,
    ) {
    }

    /**
     * @throws WalletDoesNotExist
     */
    public function __invoke(GetWalletQuery $query): WalletDetails
    {
        return $this->walletReadModel->findWallet($query->walletId)
            ?? throw WalletDoesNotExist::withId($query->walletId);
    }
}
