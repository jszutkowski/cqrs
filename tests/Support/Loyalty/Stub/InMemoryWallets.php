<?php

declare(strict_types=1);

namespace App\Tests\Support\Loyalty\Stub;

use App\Loyalty\Domain\Wallet\Exception\WalletNotFound;
use App\Loyalty\Domain\Wallet\Wallet;
use App\Loyalty\Domain\Wallet\WalletId;
use App\Loyalty\Domain\Wallet\Wallets;
use App\Shared\Application\Event\EventBus;
use App\Shared\Domain\EventSourcing\DomainEvent;
use App\Shared\Domain\EventSourcing\DomainEventsStream;

/**
 * Stores whole event streams rather than aggregate instances, so a saved
 * aggregate is genuinely rehydrated on the next read — the same round trip the
 * real event-sourced repository performs.
 */
final class InMemoryWallets implements Wallets
{
    /**
     * @var array<string, list<DomainEvent>>
     */
    private array $streams = [];

    public function __construct(
        private readonly ?EventBus $eventBus = null,
    ) {
    }

    public function get(WalletId $walletId): Wallet
    {
        $events = $this->streams[$walletId->value] ?? throw WalletNotFound::withId($walletId);

        return Wallet::reconstitute(DomainEventsStream::fromArray($events));
    }

    public function save(Wallet $wallet): void
    {
        $events = $wallet->popRecordedEvents();

        foreach ($events as $event) {
            $this->streams[$wallet->aggregateId()][] = $event;
        }

        $this->eventBus?->publish(...$events->toArray());
    }

    /**
     * @return list<DomainEvent>
     */
    public function streamOf(WalletId $walletId): array
    {
        return $this->streams[$walletId->value] ?? [];
    }

    public function balanceOf(WalletId $walletId): int
    {
        return $this->get($walletId)->balance();
    }
}
