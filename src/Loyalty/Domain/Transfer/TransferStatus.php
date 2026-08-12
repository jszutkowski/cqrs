<?php

declare(strict_types=1);

namespace App\Loyalty\Domain\Transfer;

enum TransferStatus: string
{
    case Initiated = 'initiated';
    case Completed = 'completed';
    case Failed = 'failed';

    public function isSettled(): bool
    {
        return self::Initiated !== $this;
    }
}
