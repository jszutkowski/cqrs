<?php

namespace App\Application\ReadModel\Projection;

use App\Domain\EventSourcing\DomainEventsStream;

interface ProjectionInterface
{
    public function apply(DomainEventsStream $eventsStream): void;
}
