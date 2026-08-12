<?php

declare(strict_types=1);

namespace App\Loyalty\Application\Query;

final readonly class GetTransferQuery
{
    public function __construct(
        public string $transferId,
    ) {
    }
}
