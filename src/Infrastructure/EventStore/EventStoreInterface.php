<?php
declare(strict_types=1);

namespace App\Infrastructure\EventStore;


use App\Domain\EventSourcing\NoResultException;
use App\Domain\EventSourcing\AggregateRootInterface;

interface EventStoreInterface
{
    /**
     * @throws NoResultException
     */
    public function get(string $id): AggregateRootInterface;

    public function store(AggregateRootInterface $aggregateRoot): void;
}
