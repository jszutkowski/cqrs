<?php

declare(strict_types=1);

namespace App\Tests\Unit\Loyalty\Domain\Wallet;

use App\Loyalty\Domain\Transfer\TransferId;
use App\Loyalty\Domain\Wallet\Event\PointsAdded;
use App\Loyalty\Domain\Wallet\Event\PointsRefunded;
use App\Loyalty\Domain\Wallet\Event\PointsWithdrawn;
use App\Loyalty\Domain\Wallet\Event\WalletCreated;
use App\Loyalty\Domain\Wallet\Exception\InsufficientPoints;
use App\Loyalty\Domain\Wallet\Points;
use App\Loyalty\Domain\Wallet\Wallet;
use App\Loyalty\Domain\Wallet\WalletId;
use App\Shared\Domain\EventSourcing\DomainEventsStream;
use App\Tests\Support\Loyalty\Builder\WalletBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Wallet::class)]
final class WalletTest extends TestCase
{
    private const string OCCURRED_AT = '2026-01-01 12:00:00';

    #[Test]
    public function it_records_wallet_created_when_created(): void
    {
        $walletId = WalletId::generate();

        $wallet = Wallet::create($walletId, $this->now());

        $events = $wallet->popRecordedEvents()->toArray();

        self::assertCount(1, $events);
        self::assertInstanceOf(WalletCreated::class, $events[0]);
        self::assertSame($walletId->value, $events[0]->aggregateId);
        self::assertSame(0, $wallet->balance());
    }

    #[Test]
    public function it_adds_points_to_the_balance(): void
    {
        $wallet = WalletBuilder::create()->build();

        $wallet->addPoints(Points::of(150), $this->now());

        self::assertSame(150, $wallet->balance());

        $events = $wallet->popRecordedEvents()->toArray();
        $pointsAdded = end($events);

        self::assertInstanceOf(PointsAdded::class, $pointsAdded);
        self::assertSame(150, $pointsAdded->points);
        self::assertNull($pointsAdded->transferId, 'A plain top-up must not look like a transfer leg.');
    }

    #[Test]
    public function it_withdraws_points_for_a_transfer(): void
    {
        $wallet = WalletBuilder::create()->withBalance(200)->build();
        $transferId = TransferId::generate();

        $wallet->withdraw(Points::of(80), $transferId, $this->now());

        self::assertSame(120, $wallet->balance());

        $events = $wallet->popRecordedEvents()->toArray();
        $withdrawn = end($events);

        self::assertInstanceOf(PointsWithdrawn::class, $withdrawn);
        self::assertSame(80, $withdrawn->points);
        self::assertSame($transferId->value, $withdrawn->transferId);
    }

    #[Test]
    public function it_rejects_a_withdrawal_larger_than_the_balance(): void
    {
        $wallet = WalletBuilder::create()->withBalance(50)->build();
        $wallet->popRecordedEvents();

        try {
            $wallet->withdraw(Points::of(51), TransferId::generate(), $this->now());
            self::fail('Expected the wallet to reject a withdrawal exceeding its balance.');
        } catch (InsufficientPoints) {
            self::assertSame(50, $wallet->balance(), 'A rejected withdrawal must leave the balance untouched.');
            self::assertCount(0, $wallet->popRecordedEvents(), 'A rejected withdrawal must record no event.');
        }
    }

    #[Test]
    public function it_allows_withdrawing_the_entire_balance(): void
    {
        $wallet = WalletBuilder::create()->withBalance(50)->build();

        $wallet->withdraw(Points::of(50), TransferId::generate(), $this->now());

        self::assertSame(0, $wallet->balance());
    }

    #[Test]
    public function it_restores_the_balance_on_refund(): void
    {
        $wallet = WalletBuilder::create()->withBalance(100)->build();
        $transferId = TransferId::generate();
        $wallet->withdraw(Points::of(40), $transferId, $this->now());

        $wallet->refund(Points::of(40), $transferId, $this->now());

        self::assertSame(100, $wallet->balance());

        $events = $wallet->popRecordedEvents()->toArray();
        $refunded = end($events);

        self::assertInstanceOf(PointsRefunded::class, $refunded);
        self::assertSame($transferId->value, $refunded->transferId);
    }

    #[Test]
    public function it_rebuilds_the_same_balance_from_its_event_stream(): void
    {
        $walletId = WalletId::generate();
        $transferId = TransferId::generate();

        $wallet = Wallet::create($walletId, $this->now());
        $wallet->addPoints(Points::of(300), $this->now());
        $wallet->withdraw(Points::of(120), $transferId, $this->now());
        $wallet->refund(Points::of(120), $transferId, $this->now());
        $wallet->addPoints(Points::of(30), $this->now());

        $rebuilt = Wallet::reconstitute($wallet->popRecordedEvents());

        self::assertSame(330, $rebuilt->balance());
        self::assertTrue($rebuilt->walletId()->equals($walletId));
    }

    #[Test]
    public function it_reports_the_committed_version_of_a_rebuilt_wallet(): void
    {
        $wallet = WalletBuilder::create()->withBalance(10)->build();
        $stream = $wallet->popRecordedEvents();

        $rebuilt = Wallet::reconstitute($stream);

        self::assertCount(2, $stream);
        self::assertSame(1, $rebuilt->committedVersion(), 'Two stored events mean the head sits at version 1.');
    }

    #[Test]
    public function it_starts_a_new_wallet_before_the_first_stored_version(): void
    {
        $wallet = Wallet::create(WalletId::generate(), $this->now());

        self::assertSame(-1, $wallet->committedVersion());
    }

    #[Test]
    public function it_forgets_events_once_they_are_popped(): void
    {
        $wallet = WalletBuilder::create()->build();

        self::assertCount(1, $wallet->popRecordedEvents());
        self::assertCount(0, $wallet->popRecordedEvents());
    }

    #[Test]
    public function it_rebuilds_an_empty_wallet_from_an_empty_stream(): void
    {
        $wallet = Wallet::reconstitute(DomainEventsStream::of());

        self::assertSame(0, $wallet->balance());
    }

    private function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable(self::OCCURRED_AT);
    }
}
