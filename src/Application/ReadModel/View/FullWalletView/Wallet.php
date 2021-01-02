<?php

declare(strict_types=1);

namespace App\Application\ReadModel\View\FullWalletView;

class Wallet
{
    public string $walletId;
    public int $balance;

    /**
     * @var Points[]
     */
    public array $points = [];

    public function __construct(string $walletId, int $balance)
    {
        $this->walletId = $walletId;
        $this->balance = $balance;
    }
}
