<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\EventSourcing\EventStoreInterface;
use App\Domain\EventSourcing\NoResultException;
use App\Domain\Exception\DomainException;
use App\Domain\Loyalty\Wallet;
use App\Domain\Loyalty\WalletsRepository;

class EventSourcedWalletsRepository implements WalletsRepository
{
    private EventStoreInterface $eventStore;

    public function __construct(EventStoreInterface $eventStore)
    {
        $this->eventStore = $eventStore;
    }

    public function save(Wallet $wallet): void
    {
        $this->eventStore->store($wallet->getAggregateId(), $wallet->getUncommittedEvents());
    }

    /**
     * @throws NoResultException
     * @throws DomainException
     */
    public function find(string $id): Wallet
    {
        $events = $this->eventStore->get($id);

        return Wallet::fromEvents((array) $events->getIterator());
    }
}
