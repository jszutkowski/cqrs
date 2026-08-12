<?php

declare(strict_types=1);

namespace App\Loyalty\Application\ReadModel;

/**
 * Read side port. Deliberately not a repository: it never returns aggregates,
 * only flat views built by the projections, and it is queried synchronously.
 */
interface WalletReadModel
{
    /**
     * @return list<WalletSummary>
     */
    public function listWallets(): array;

    public function findWallet(string $walletId): ?WalletDetails;
}
