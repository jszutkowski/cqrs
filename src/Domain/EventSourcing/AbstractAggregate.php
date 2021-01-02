<?php

declare(strict_types=1);

namespace App\Domain\EventSourcing;

use App\Domain\Event;
use App\Domain\Exception\DomainException;

abstract class AbstractAggregate implements AggregateInterface
{
    protected string $aggregateId;

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

    public function getAggregateId(): string
    {
        return $this->aggregateId;
    }

    public function setAggregateId(string $aggregateId): void
    {
        $this->aggregateId = $aggregateId;
    }

    public function getUncommittedEvents(): DomainEventsStream
    {
        $stream = new DomainEventsStream($this->uncommittedEvents);

        $this->uncommittedEvents = [];

        return $stream;
    }

    /**
     * @throws DomainException
     */
    protected function recordThat(Event $event): void
    {
        $event->setVersion(++$this->version);

        $this->uncommittedEvents[] = $event;

        $this->handle($event);
    }

    /**
     * @throws DomainException
     */
    abstract protected function handle(Event $event): void;
}
