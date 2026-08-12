<?php

declare(strict_types=1);

namespace App\Loyalty\Domain\Transfer;

use App\Loyalty\Domain\Transfer\Exception\TransferNotFound;
use App\Shared\Domain\EventSourcing\Exception\ConcurrencyConflict;

interface Transfers
{
    /**
     * @throws TransferNotFound
     */
    public function get(TransferId $transferId): Transfer;

    /**
     * @throws ConcurrencyConflict
     */
    public function save(Transfer $transfer): void;
}
