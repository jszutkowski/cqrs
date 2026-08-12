<?php

declare(strict_types=1);

namespace App\Loyalty\Domain\Wallet\Event;

use App\Shared\Domain\EventSourcing\DomainEvent;

final readonly class WalletCreated extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        \DateTimeImmutable $occurredAt,
    ) {
        parent::__construct($aggregateId, $occurredAt);
    }
}
