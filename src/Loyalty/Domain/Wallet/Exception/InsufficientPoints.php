<?php

declare(strict_types=1);

namespace App\Loyalty\Domain\Wallet\Exception;

use App\Loyalty\Domain\Wallet\Points;
use App\Loyalty\Domain\Wallet\WalletId;

final class InsufficientPoints extends \DomainException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function forWithdrawal(WalletId $walletId, Points $requested, int $balance): self
    {
        return new self(\sprintf(
            'Cannot withdraw %d points from wallet "%s": balance is %d.',
            $requested->amount,
            $walletId->value,
            $balance,
        ));
    }
}
