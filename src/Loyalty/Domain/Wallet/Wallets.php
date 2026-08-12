<?php

declare(strict_types=1);

namespace App\Loyalty\Domain\Wallet;

use App\Loyalty\Domain\Wallet\Exception\WalletNotFound;
use App\Shared\Domain\EventSourcing\Exception\ConcurrencyConflict;

interface Wallets
{
    /**
     * @throws WalletNotFound
     */
    public function get(WalletId $walletId): Wallet;

    /**
     * @throws ConcurrencyConflict
     */
    public function save(Wallet $wallet): void;
}
