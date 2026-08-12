<?php

declare(strict_types=1);

namespace App\Loyalty\Domain\Wallet\Event;

use App\Shared\Domain\EventSourcing\DomainEvent;

final readonly class PointsWithdrawn extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        public int $points,
        public string $transferId,
        \DateTimeImmutable $occurredAt,
    ) {
        parent::__construct($aggregateId, $occurredAt);
    }
}
