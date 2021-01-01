<?php
declare(strict_types=1);

namespace App\Application\Command;

use App\Application\System\Command;

class WithdrawPoints implements Command
{
    private string $walletId;
    private int $points;

    public function __construct(string $walletId, int $points)
    {
        $this->walletId = $walletId;
        $this->points = $points;
    }

    public function getWalletId(): string
    {
        return $this->walletId;
    }

    public function getPoints(): int
    {
        return $this->points;
    }
}
