<?php

declare(strict_types=1);

namespace App\Application\ReadModel\Query;

use App\Application\ReadModel\View\FullWalletView\Wallet;

interface Wallets
{
    /**
     * @return array<string, int>
     *
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function getWallets(): array;

    public function getWallet(string $id): ?Wallet;
}
