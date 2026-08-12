<?php

declare(strict_types=1);

namespace App\Loyalty\Domain\Transfer\Exception;

use App\Loyalty\Domain\Transfer\TransferId;

final class TransferNotFound extends \DomainException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function withId(TransferId $transferId): self
    {
        return new self(\sprintf('Transfer "%s" does not exist.', $transferId->value));
    }
}
