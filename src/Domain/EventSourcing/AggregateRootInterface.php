<?php
declare(strict_types=1);

namespace App\Domain\EventSourcing;


interface AggregateRootInterface
{
    public function getAggregateRootId(): string;

    public function getUncommittedEvents(): DomainEventsStream;

    public function initializeState(DomainEventsStream $stream): void;
}
