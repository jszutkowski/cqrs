<?php

declare(strict_types=1);

namespace App\Infrastructure\EventListener;

use App\Domain\EventSourcing\DomainEventsStream;
use Symfony\Contracts\EventDispatcher\Event;

class ProjectionUpdated extends Event
{
    public const EVENT_NAME = 'projection.updated';

    private DomainEventsStream $domainEvents;

    public function __construct(DomainEventsStream $domainEvents)
    {
        $this->domainEvents = $domainEvents;
    }

    public function getDomainEvents(): DomainEventsStream
    {
        return $this->domainEvents;
    }
}
