<?php
declare(strict_types=1);

namespace App\Domain\EventSourcing;


use App\Domain\Event;
use App\Domain\Exception\DomainException;

abstract class AbstractAggregateRoot implements AggregateRootInterface
{
    protected string $aggregateRootId;

    /**
     * @var Event[]
     */
    protected array $uncommittedEvents = [];

    protected int $version = -1;

    final protected function __construct()
    {

    }

    public function initializeState(DomainEventsStream $stream): void
    {
        $this->uncommittedEvents = [];

        foreach ($stream as $event) {
            $this->version = $event->getVersion();
            $this->handle($event);
        }
    }

    public function getAggregateRootId(): string
    {
        return $this->aggregateRootId;
    }

    public function setAggregateRootId(string $aggregateRootId): void
    {
        $this->aggregateRootId = $aggregateRootId;
    }

    public function getUncommittedEvents(): DomainEventsStream
    {
        $stream = new DomainEventsStream($this->uncommittedEvents);

        $this->uncommittedEvents = [];

        return $stream;
    }

    /**
     * @param Event $event
     * @throws DomainException
     */
    protected function recordThat(Event $event): void
    {
        $event->setVersion(++$this->version);

        $this->uncommittedEvents[] = $event;

        $this->handle($event);
    }

    /**
     * @param Event $event
     * @throws DomainException
     */
    abstract protected function handle(Event $event): void;
}
