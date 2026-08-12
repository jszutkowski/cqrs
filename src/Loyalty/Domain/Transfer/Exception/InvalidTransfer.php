<?php

declare(strict_types=1);

namespace App\Loyalty\Domain\Transfer\Exception;

use App\Loyalty\Domain\Wallet\WalletId;

final class InvalidTransfer extends \DomainException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function betweenSameWallet(WalletId $walletId): self
    {
        return new self(\sprintf(
            'Transfer source and target must differ, both are "%s".',
            $walletId->value,
        ));
    }
}
