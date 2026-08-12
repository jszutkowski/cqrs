<?php

declare(strict_types=1);

namespace App\Loyalty\Domain\Wallet\Event;

use App\Shared\Domain\EventSourcing\DomainEvent;

final readonly class PointsAdded extends DomainEvent
{
    /**
     * @param string|null $transferId set when the points arrive as the deposit
     *                                leg of a transfer, null for a plain top-up
     */
    public function __construct(
        string $aggregateId,
        public int $points,
        public ?string $transferId,
        \DateTimeImmutable $occurredAt,
    ) {
        parent::__construct($aggregateId, $occurredAt);
    }
}
