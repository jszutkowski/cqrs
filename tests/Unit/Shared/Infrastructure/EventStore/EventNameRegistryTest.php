<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\EventStore;

use App\Loyalty\Domain\Wallet\Event\PointsAdded;
use App\Loyalty\Domain\Wallet\Event\WalletCreated;
use App\Shared\Infrastructure\EventStore\EventNameRegistry;
use App\Shared\Infrastructure\EventStore\UnregisteredEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(EventNameRegistry::class)]
final class EventNameRegistryTest extends TestCase
{
    private EventNameRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new EventNameRegistry([
            'wallet_created' => WalletCreated::class,
            'points_added' => PointsAdded::class,
        ]);
    }

    #[Test]
    public function it_maps_an_event_to_its_stored_name(): void
    {
        $event = new WalletCreated('c3f1a5d0-0000-4000-8000-000000000000', new \DateTimeImmutable());

        self::assertSame('wallet_created', $this->registry->nameOf($event));
    }

    #[Test]
    public function it_maps_a_stored_name_back_to_its_class(): void
    {
        self::assertSame(PointsAdded::class, $this->registry->classOf('points_added'));
    }

    #[Test]
    public function it_refuses_to_store_an_unregistered_event(): void
    {
        $registry = new EventNameRegistry([]);

        $this->expectException(UnregisteredEvent::class);

        $registry->nameOf(new WalletCreated('c3f1a5d0-0000-4000-8000-000000000000', new \DateTimeImmutable()));
    }

    #[Test]
    public function it_refuses_to_read_an_unknown_stored_name(): void
    {
        $this->expectException(UnregisteredEvent::class);

        $this->registry->classOf('points_teleported');
    }
}
