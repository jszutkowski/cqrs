<?php

declare(strict_types=1);

namespace App\Loyalty\Domain\Wallet\Exception;

use App\Loyalty\Domain\Wallet\WalletId;

final class WalletNotFound extends \DomainException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function withId(WalletId $walletId): self
    {
        return new self(\sprintf('Wallet "%s" does not exist.', $walletId->value));
    }
}
