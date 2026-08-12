<?php

declare(strict_types=1);

namespace App\Shared\Domain\EventSourcing;

abstract class AggregateRoot
{
    /**
     * @var list<DomainEvent>
     */
    private array $recordedEvents = [];

    /**
     * Version of the last event already persisted in the event store (-1 for a
     * brand-new aggregate). Passed to the store as the expected version so that
     * concurrent writers conflict instead of silently interleaving streams.
     */
    private int $committedVersion = -1;

    final protected function __construct()
    {
    }

    public static function reconstitute(DomainEventsStream $stream): static
    {
        $aggregate = new static();

        foreach ($stream as $event) {
            $aggregate->apply($event);
            ++$aggregate->committedVersion;
        }

        return $aggregate;
    }

    abstract public function aggregateId(): string;

    /**
     * Name this aggregate's stream carries in the event store.
     *
     * Declared by the aggregate rather than derived from the class name, for
     * the same reason event names are: a stream outlives the namespace layout
     * that produced it, and renaming a class must not orphan its history.
     */
    abstract public static function aggregateType(): string;

    public function committedVersion(): int
    {
        return $this->committedVersion;
    }

    public function popRecordedEvents(): DomainEventsStream
    {
        $stream = DomainEventsStream::fromArray($this->recordedEvents);

        $this->recordedEvents = [];

        return $stream;
    }

    protected function recordThat(DomainEvent $event): void
    {
        $this->recordedEvents[] = $event;

        $this->apply($event);
    }

    /**
     * The only place allowed to mutate aggregate state.
     *
     * Each aggregate dispatches explicitly rather than through a derived
     * `apply<EventName>` method name: a rename then stays a refactor instead of
     * a silently unhandled event, and static analysis can narrow the event type
     * inside every branch.
     */
    abstract protected function apply(DomainEvent $event): void;
}
