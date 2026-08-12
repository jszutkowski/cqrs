<?php

declare(strict_types=1);

namespace App\Loyalty\Domain\Transfer\Event;

use App\Shared\Domain\EventSourcing\DomainEvent;

final readonly class TransferFailed extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        public string $reason,
        \DateTimeImmutable $occurredAt,
    ) {
        parent::__construct($aggregateId, $occurredAt);
    }
}
