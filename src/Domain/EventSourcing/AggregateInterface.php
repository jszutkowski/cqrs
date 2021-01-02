<?php

declare(strict_types=1);

namespace App\Domain\EventSourcing;

interface AggregateInterface
{
    public function getAggregateId(): string;

    public function getUncommittedEvents(): DomainEventsStream;

    public function initializeState(DomainEventsStream $stream): void;
}
