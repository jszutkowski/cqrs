<?php

declare(strict_types=1);

namespace App\Loyalty\Application\Exception;

use App\Shared\Application\Exception\ApplicationException;

final class WalletDoesNotExist extends ApplicationException
{
    public static function withId(string $walletId): self
    {
        return new self(\sprintf('Wallet "%s" does not exist.', $walletId));
    }
}
