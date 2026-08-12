<?php

declare(strict_types=1);

namespace App\Loyalty\Domain\Wallet;

use App\Loyalty\Domain\Transfer\TransferId;
use App\Loyalty\Domain\Wallet\Event\PointsAdded;
use App\Loyalty\Domain\Wallet\Event\PointsRefunded;
use App\Loyalty\Domain\Wallet\Event\PointsWithdrawn;
use App\Loyalty\Domain\Wallet\Event\WalletCreated;
use App\Loyalty\Domain\Wallet\Exception\InsufficientPoints;
use App\Shared\Domain\EventSourcing\AggregateRoot;
use App\Shared\Domain\EventSourcing\DomainEvent;
use App\Shared\Domain\EventSourcing\Exception\UnknownDomainEvent;

final class Wallet extends AggregateRoot
{
    private WalletId $walletId;
    private int $balance = 0;

    public static function create(WalletId $walletId, \DateTimeImmutable $occurredAt): self
    {
        $wallet = new self();

        $wallet->recordThat(new WalletCreated($walletId->value, $occurredAt));

        return $wallet;
    }

    public function addPoints(Points $points, \DateTimeImmutable $occurredAt): void
    {
        $this->recordThat(new PointsAdded($this->walletId->value, $points->amount, null, $occurredAt));
    }

    /**
     * Deposit leg of a transfer — same balance effect as a plain top-up, but the
     * event keeps the transfer id so the transfer process can observe completion.
     */
    public function deposit(Points $points, TransferId $transferId, \DateTimeImmutable $occurredAt): void
    {
        $this->recordThat(new PointsAdded($this->walletId->value, $points->amount, $transferId->value, $occurredAt));
    }

    /**
     * @throws InsufficientPoints
     */
    public function withdraw(Points $points, TransferId $transferId, \DateTimeImmutable $occurredAt): void
    {
        if ($this->balance < $points->amount) {
            throw InsufficientPoints::forWithdrawal($this->walletId, $points, $this->balance);
        }

        $this->recordThat(new PointsWithdrawn($this->walletId->value, $points->amount, $transferId->value, $occurredAt));
    }

    public function refund(Points $points, TransferId $transferId, \DateTimeImmutable $occurredAt): void
    {
        $this->recordThat(new PointsRefunded($this->walletId->value, $points->amount, $transferId->value, $occurredAt));
    }

    public function aggregateId(): string
    {
        return $this->walletId->value;
    }

    public function walletId(): WalletId
    {
        return $this->walletId;
    }

    public function balance(): int
    {
        return $this->balance;
    }

    protected function apply(DomainEvent $event): void
    {
        match (true) {
            $event instanceof WalletCreated => $this->walletId = WalletId::fromString($event->aggregateId),
            $event instanceof PointsAdded, $event instanceof PointsRefunded => $this->balance += $event->points,
            $event instanceof PointsWithdrawn => $this->balance -= $event->points,
            default => throw UnknownDomainEvent::inAggregate($event::class, self::class),
        };
    }
}
