<?php
declare(strict_types=1);

namespace App\Infrastructure\Loyalty\Repository;


use App\Domain\EventSourcing\NoResultException;
use App\Domain\Loyalty\Wallet;
use App\Domain\Loyalty\Wallets;
use App\Infrastructure\EventStore\EventStoreInterface;

class EventSourcingWallets implements Wallets
{
    /**
     * @var EventStoreInterface
     */
    private EventStoreInterface $eventStore;

    public function __construct(EventStoreInterface $eventStore)
    {
        $this->eventStore = $eventStore;
    }

    public function store(Wallet $wallet): void
    {
        $this->eventStore->store($wallet);
    }

    /**
     * @param string $uuid
     * @return Wallet
     * @throws NoResultException
     */
    public function get(string $uuid): Wallet
    {
        return $this->eventStore->get($uuid);
    }
}
