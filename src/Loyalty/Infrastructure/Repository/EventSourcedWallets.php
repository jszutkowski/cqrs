<?php

declare(strict_types=1);

namespace App\Loyalty\Infrastructure\Repository;

use App\Loyalty\Domain\Wallet\Exception\WalletNotFound;
use App\Loyalty\Domain\Wallet\Wallet;
use App\Loyalty\Domain\Wallet\WalletId;
use App\Loyalty\Domain\Wallet\Wallets;
use App\Shared\Application\Event\EventBus;
use App\Shared\Domain\EventSourcing\EventStore;

final readonly class EventSourcedWallets implements Wallets
{
    public function __construct(
        private EventStore $eventStore,
        private EventBus $eventBus,
    ) {
    }

    public function get(WalletId $walletId): Wallet
    {
        $stream = $this->eventStore->load(Wallet::aggregateType(), $walletId->value);

        if ($stream->isEmpty()) {
            throw WalletNotFound::withId($walletId);
        }

        return Wallet::reconstitute($stream);
    }

    public function save(Wallet $wallet): void
    {
        $events = $wallet->popRecordedEvents();

        $this->eventStore->append(Wallet::aggregateType(), $wallet->aggregateId(), $events, $wallet->committedVersion());

        $this->eventBus->publish(...$events->toArray());
    }
}
