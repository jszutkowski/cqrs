<?php

declare(strict_types=1);

namespace App\Domain\EventSourcing;

use App\Domain\Event;
use Webmozart\Assert\Assert;

/**
 * @implements \IteratorAggregate<Event>
 */
class DomainEventsStream implements \IteratorAggregate
{
    /**
     * @param Event[] $events
     */
    private array $events;

    /**
     * @param Event[] $events
     */
    public function __construct(array $events)
    {
        Assert::allSubclassOf($events, Event::class);

        $this->events = $events;
    }

    /**
     * @return \ArrayIterator|Event[]
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->events);
    }
}
