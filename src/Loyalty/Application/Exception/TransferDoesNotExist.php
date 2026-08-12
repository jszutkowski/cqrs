<?php

declare(strict_types=1);

namespace App\Loyalty\Application\Exception;

use App\Shared\Application\Exception\ApplicationException;

final class TransferDoesNotExist extends ApplicationException
{
    public static function withId(string $transferId): self
    {
        return new self(\sprintf('Transfer "%s" does not exist.', $transferId));
    }
}
