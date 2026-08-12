<?php

declare(strict_types=1);

namespace App\Loyalty\Domain\Transfer\Event;

use App\Shared\Domain\EventSourcing\DomainEvent;

final readonly class TransferCompleted extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        \DateTimeImmutable $occurredAt,
    ) {
        parent::__construct($aggregateId, $occurredAt);
    }
}
