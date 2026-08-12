<?php

declare(strict_types=1);

namespace App\Loyalty\Domain\Transfer\Exception;

use App\Loyalty\Domain\Transfer\TransferId;
use App\Loyalty\Domain\Transfer\TransferStatus;

final class TransferAlreadySettled extends \DomainException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function withStatus(TransferId $transferId, TransferStatus $status): self
    {
        return new self(\sprintf(
            'Transfer "%s" is already settled as "%s" and cannot change state.',
            $transferId->value,
            $status->value,
        ));
    }
}
