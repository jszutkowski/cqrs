<?php

namespace App\Domain\Loyalty;


class Points
{
    private int $amo;

    public function __construct(int $amount)
    {
        $this->amo = $amount;
    }

    public function getAmount(): int
    {
        return $this->amo;
    }
}
