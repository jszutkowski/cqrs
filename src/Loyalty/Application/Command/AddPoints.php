<?php

declare(strict_types=1);

namespace App\Loyalty\Application\Command;

use App\Shared\Application\Command\Command;

final readonly class AddPoints implements Command
{
    public function __construct(
        public string $walletId,
        public int $points,
    ) {
    }
}
