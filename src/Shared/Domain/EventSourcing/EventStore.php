<?php

declare(strict_types=1);

namespace App\Shared\Domain\EventSourcing;

use App\Shared\Domain\EventSourcing\Exception\ConcurrencyConflict;

interface EventStore
{
    /**
     * Appends events to the aggregate's stream, starting at $expectedVersion + 1.
     *
     * @throws ConcurrencyConflict when another writer has appended to the stream
     *                             since the aggregate was loaded
     */
    public function append(string $aggregateId, DomainEventsStream $events, int $expectedVersion): void;

    /**
     * Returns the full stream for the aggregate, oldest first.
     * An unknown aggregate id yields an empty stream — translating that into a
     * domain-specific "not found" is the repository's job.
     */
    public function load(string $aggregateId): DomainEventsStream;
}
