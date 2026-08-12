<?php

declare(strict_types=1);

namespace App\Loyalty\Application\Exception;

use App\Shared\Application\Exception\ApplicationException;

final class TransferRejected extends ApplicationException
{
    public static function because(string $reason): self
    {
        return new self(\sprintf('Transfer rejected: %s', $reason));
    }
}
