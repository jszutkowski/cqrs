<?php
declare(strict_types=1);

namespace App\Domain\EventSourcing;


use App\Domain\Event;
use Webmozart\Assert\Assert;

class DomainEventsStream implements \IteratorAggregate
{
    private array $events;

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
