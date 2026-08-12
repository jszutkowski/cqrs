<?php

declare(strict_types=1);

namespace App\Loyalty\Domain\Wallet\Event;

use App\Shared\Domain\EventSourcing\DomainEvent;

/**
 * Compensation leg of a failed transfer: the points withdrawn earlier are
 * returned to the source wallet.
 */
final readonly class PointsRefunded extends DomainEvent
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
