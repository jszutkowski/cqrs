<?php

declare(strict_types=1);

namespace App\Application\ReadModel\View;

class SimpleWallet
{
    public string $walletId;
    public int $balance;

    public function __construct(string $walletId, int $balance)
    {
        $this->walletId = $walletId;
        $this->balance = $balance;
    }
}
