<?php

declare(strict_types=1);

namespace App\Tests\Integration\Shared\Infrastructure\EventStore;

use App\Loyalty\Domain\Transfer\TransferId;
use App\Loyalty\Domain\Wallet\Event\PointsAdded;
use App\Loyalty\Domain\Wallet\Event\PointsWithdrawn;
use App\Loyalty\Domain\Wallet\Event\WalletCreated;
use App\Loyalty\Domain\Wallet\WalletId;
use App\Shared\Domain\EventSourcing\DomainEventsStream;
use App\Shared\Domain\EventSourcing\Exception\ConcurrencyConflict;
use App\Shared\Infrastructure\EventStore\DbalEventStore;
use App\Tests\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(DbalEventStore::class)]
final class DbalEventStoreTest extends IntegrationTestCase
{
    private DbalEventStore $eventStore;

    protected function setUp(): void
    {
        parent::setUp();

        $eventStore = self::getContainer()->get(DbalEventStore::class);
        \assert($eventStore instanceof DbalEventStore);
        $this->eventStore = $eventStore;
    }

    #[Test]
    public function it_returns_an_empty_stream_for_an_unknown_aggregate(): void
    {
        $stream = $this->eventStore->load(WalletId::generate()->value);

        self::assertTrue($stream->isEmpty());
    }

    #[Test]
    public function it_round_trips_events_through_the_database(): void
    {
        $walletId = WalletId::generate();
        $transferId = TransferId::generate();
        $occurredAt = new \DateTimeImmutable('2026-01-01 12:00:00');

        $this->eventStore->append($walletId->value, DomainEventsStream::of(
            new WalletCreated($walletId->value, $occurredAt),
            new PointsAdded($walletId->value, 300, null, $occurredAt),
            new PointsWithdrawn($walletId->value, 120, $transferId->value, $occurredAt),
        ), -1);

        $stream = $this->eventStore->load($walletId->value)->toArray();

        self::assertCount(3, $stream);
        self::assertInstanceOf(WalletCreated::class, $stream[0]);
        self::assertInstanceOf(PointsAdded::class, $stream[1]);
        self::assertInstanceOf(PointsWithdrawn::class, $stream[2]);

        self::assertSame($walletId->value, $stream[1]->aggregateId);
        self::assertSame(300, $stream[1]->points);
        self::assertNull($stream[1]->transferId);
        self::assertSame($transferId->value, $stream[2]->transferId);
        self::assertEquals($occurredAt, $stream[2]->occurredAt);
    }

    #[Test]
    public function it_keeps_events_in_the_order_they_were_appended(): void
    {
        $walletId = WalletId::generate();
        $occurredAt = new \DateTimeImmutable('2026-01-01 12:00:00');

        $this->eventStore->append($walletId->value, DomainEventsStream::of(
            new WalletCreated($walletId->value, $occurredAt),
        ), -1);

        // All three share a timestamp, so only the version column can order them.
        $this->eventStore->append($walletId->value, DomainEventsStream::of(
            new PointsAdded($walletId->value, 10, null, $occurredAt),
            new PointsAdded($walletId->value, 20, null, $occurredAt),
        ), 0);

        $amounts = array_map(
            static fn (object $event): ?int => $event instanceof PointsAdded ? $event->points : null,
            $this->eventStore->load($walletId->value)->toArray(),
        );

        self::assertSame([null, 10, 20], $amounts);
    }

    #[Test]
    public function it_rejects_a_write_based_on_a_stale_version(): void
    {
        $walletId = WalletId::generate();
        $occurredAt = new \DateTimeImmutable('2026-01-01 12:00:00');

        $this->eventStore->append($walletId->value, DomainEventsStream::of(
            new WalletCreated($walletId->value, $occurredAt),
        ), -1);

        // Two concurrent writers both loaded the wallet at version 0.
        $this->eventStore->append($walletId->value, DomainEventsStream::of(
            new PointsAdded($walletId->value, 10, null, $occurredAt),
        ), 0);

        $this->expectException(ConcurrencyConflict::class);

        $this->eventStore->append($walletId->value, DomainEventsStream::of(
            new PointsAdded($walletId->value, 20, null, $occurredAt),
        ), 0);
    }

    #[Test]
    public function it_does_not_persist_the_losing_write_of_a_conflict(): void
    {
        $walletId = WalletId::generate();
        $occurredAt = new \DateTimeImmutable('2026-01-01 12:00:00');

        $this->eventStore->append($walletId->value, DomainEventsStream::of(
            new WalletCreated($walletId->value, $occurredAt),
        ), -1);
        $this->eventStore->append($walletId->value, DomainEventsStream::of(
            new PointsAdded($walletId->value, 10, null, $occurredAt),
        ), 0);

        try {
            $this->eventStore->append($walletId->value, DomainEventsStream::of(
                new PointsAdded($walletId->value, 20, null, $occurredAt),
            ), 0);
        } catch (ConcurrencyConflict) {
            // expected
        }

        $stream = $this->eventStore->load($walletId->value)->toArray();

        self::assertCount(2, $stream, 'The rejected write must leave no trace in the stream.');
    }

    #[Test]
    public function it_writes_nothing_for_an_empty_stream(): void
    {
        $walletId = WalletId::generate();

        $this->eventStore->append($walletId->value, DomainEventsStream::of(), -1);

        self::assertTrue($this->eventStore->load($walletId->value)->isEmpty());
    }

    #[Test]
    public function it_stores_the_registered_name_instead_of_the_class_name(): void
    {
        $walletId = WalletId::generate();

        $this->eventStore->append($walletId->value, DomainEventsStream::of(
            new WalletCreated($walletId->value, new \DateTimeImmutable('2026-01-01 12:00:00')),
        ), -1);

        $storedName = $this->connection->fetchOne(
            'SELECT event_name FROM events WHERE aggregate_id = :aggregateId',
            ['aggregateId' => $walletId->value],
        );

        self::assertSame('wallet_created', $storedName);
    }
}
