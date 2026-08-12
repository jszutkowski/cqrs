<?php

declare(strict_types=1);

namespace App\Loyalty\Application\ReadModel;

interface TransferReadModel
{
    public function findTransfer(string $transferId): ?TransferSummary;
}
