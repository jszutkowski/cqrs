<?php

declare(strict_types=1);

namespace App\Loyalty\Domain\Transfer\Event;

use App\Shared\Domain\EventSourcing\DomainEvent;

final readonly class TransferInitiated extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        public string $sourceWalletId,
        public string $targetWalletId,
        public int $points,
        \DateTimeImmutable $occurredAt,
    ) {
        parent::__construct($aggregateId, $occurredAt);
    }
}
