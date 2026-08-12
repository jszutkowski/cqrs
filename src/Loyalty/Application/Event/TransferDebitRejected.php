<?php

declare(strict_types=1);

namespace App\Loyalty\Application\Event;

use App\Shared\Application\Event\ProcessEvent;

/**
 * The source wallet refused the debit — too few points, or the wallet is gone.
 * Nothing has moved, so no compensation is owed; only the transfer itself has
 * to be settled, and TransferProcessManager decides that.
 */
final readonly class TransferDebitRejected implements ProcessEvent
{
    public function __construct(
        public string $transferId,
        public string $reason,
    ) {
    }

    public function transferId(): string
    {
        return $this->transferId;
    }
}
