<?php

declare(strict_types=1);

namespace App\Domain\Loyalty\Events;

use App\Domain\Event;

class WalletCreated extends Event
{
    public function __construct(string $walletId)
    {
        parent::__construct($walletId);
    }
}
