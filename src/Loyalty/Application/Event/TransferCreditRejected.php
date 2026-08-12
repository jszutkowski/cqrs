<?php

declare(strict_types=1);

namespace App\Loyalty\Application\Event;

use App\Shared\Application\Event\ProcessEvent;

/**
 * The target wallet refused the credit after the source was already debited.
 * This is the case that owes compensation, so the event carries what a refund
 * needs — TransferProcessManager owns the decision to issue one.
 */
final readonly class TransferCreditRejected implements ProcessEvent
{
    public function __construct(
        public string $transferId,
        public string $sourceWalletId,
        public int $points,
        public string $reason,
    ) {
    }

    public function transferId(): string
    {
        return $this->transferId;
    }
}
