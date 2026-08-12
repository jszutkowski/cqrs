<?php

declare(strict_types=1);

namespace App\Loyalty\Application\Command;

use App\Shared\Application\Command\Command;

final readonly class DepositPoints implements Command
{
    /**
     * Carries the source wallet id so the handler can order a compensating
     * refund without reaching back to the transfer aggregate.
     */
    public function __construct(
        public string $walletId,
        public string $sourceWalletId,
        public int $points,
        public string $transferId,
    ) {
    }
}
