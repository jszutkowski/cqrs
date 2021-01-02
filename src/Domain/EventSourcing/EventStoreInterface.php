<?php

declare(strict_types=1);

namespace App\Domain\EventSourcing;

interface EventStoreInterface
{
    /**
     * @throws NoResultException
     */
    public function get(string $aggregateId): DomainEventsStream;

    public function store(string $aggregateId, DomainEventsStream $events): void;
}
