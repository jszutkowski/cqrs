<?php

declare(strict_types=1);

namespace App\Application\ReadModel\View\FullWalletView;

class Points
{
    public int $amount;
    public string $createdAt;

    public function __construct(int $amount, string $createdAt)
    {
        $this->amount = $amount;
        $this->createdAt = $createdAt;
    }
}
