<?php

declare(strict_types=1);

namespace App\Shared\Domain\EventSourcing;

/**
 * A domain event is an immutable fact. It intentionally carries no version —
 * ordering and optimistic locking are the event stream's concern, so an event
 * can never be "re-stamped" after the fact.
 *
 * Payload fields are primitives on purpose: events are long-lived serialization
 * contracts and must not break when value-object internals change.
 */
abstract readonly class DomainEvent
{
    protected function __construct(
        public string $aggregateId,
        public \DateTimeImmutable $occurredAt,
    ) {
    }
}
