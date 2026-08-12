<?php

declare(strict_types=1);

namespace App\Shared\Domain\EventSourcing;

use Assert\Assertion;

/**
 * Ordered, immutable sequence of domain events belonging to one aggregate.
 *
 * @implements \IteratorAggregate<int, DomainEvent>
 */
final readonly class DomainEventsStream implements \IteratorAggregate, \Countable
{
    /**
     * @param list<DomainEvent> $events
     */
    private function __construct(
        private array $events,
    ) {
    }

    public static function of(DomainEvent ...$events): self
    {
        return new self(array_values($events));
    }

    /**
     * @param list<DomainEvent> $events
     */
    public static function fromArray(array $events): self
    {
        Assertion::allIsInstanceOf($events, DomainEvent::class);

        return new self(array_values($events));
    }

    public function isEmpty(): bool
    {
        return [] === $this->events;
    }

    public function count(): int
    {
        return \count($this->events);
    }

    /**
     * @return \Traversable<int, DomainEvent>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->events;
    }

    /**
     * @return list<DomainEvent>
     */
    public function toArray(): array
    {
        return $this->events;
    }
}
