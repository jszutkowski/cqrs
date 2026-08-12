<?php

declare(strict_types=1);

namespace App\Loyalty\Application\Query;

final readonly class GetWalletQuery
{
    public function __construct(
        public string $walletId,
    ) {
    }
}
