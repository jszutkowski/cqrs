<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Application\System\Command;

class AddPoints implements Command
{
    private string $walletId;
    private int $amount;

    public function __construct(string $walletId, int $amount)
    {
        $this->walletId = $walletId;
        $this->amount = $amount;
    }

    public function getWalletId(): string
    {
        return $this->walletId;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }
}
