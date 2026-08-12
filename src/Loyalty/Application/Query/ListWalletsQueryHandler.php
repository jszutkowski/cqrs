<?php

declare(strict_types=1);

namespace App\Loyalty\Application\Query;

use App\Loyalty\Application\ReadModel\WalletReadModel;
use App\Loyalty\Application\ReadModel\WalletSummary;

final readonly class ListWalletsQueryHandler
{
    public function __construct(
        private WalletReadModel $walletReadModel,
    ) {
    }

    /**
     * @return list<WalletSummary>
     */
    public function __invoke(ListWalletsQuery $query): array
    {
        return $this->walletReadModel->listWallets();
    }
}
