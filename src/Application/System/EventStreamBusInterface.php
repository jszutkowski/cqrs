<?php

namespace App\Application\System;

use App\Domain\EventSourcing\DomainEventsStream;
use Symfony\Component\Messenger\Envelope;

interface EventStreamBusInterface
{
    /**
     * @param DomainEventsStream|Envelope|object $event
     */
    public function dispatch(object $event): void;
}
