<?php

declare(strict_types=1);

namespace App\Tests\Unit\Loyalty\Application\Command;

use App\Loyalty\Application\Command\AddPoints;
use App\Loyalty\Application\Command\AddPointsHandler;
use App\Loyalty\Application\Exception\WalletDoesNotExist;
use App\Loyalty\Domain\Wallet\Exception\WalletNotFound;
use App\Loyalty\Domain\Wallet\WalletId;
use App\Tests\Support\Loyalty\Builder\WalletBuilder;
use App\Tests\Support\Loyalty\Stub\InMemoryWallets;
use Assert\InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

#[CoversClass(AddPointsHandler::class)]
final class AddPointsHandlerTest extends TestCase
{
    private InMemoryWallets $wallets;
    private AddPointsHandler $handler;

    protected function setUp(): void
    {
        $this->wallets = new InMemoryWallets();
        $this->handler = new AddPointsHandler($this->wallets, new MockClock('2026-01-01 12:00:00'));
    }

    #[Test]
    public function it_increases_the_stored_balance(): void
    {
        $walletId = WalletId::generate();
        $this->wallets->save(WalletBuilder::create()->withId($walletId)->build());

        ($this->handler)(new AddPoints($walletId->value, 250));

        self::assertSame(250, $this->wallets->balanceOf($walletId));
    }

    #[Test]
    public function it_translates_a_missing_wallet_into_an_application_exception(): void
    {
        $this->expectException(WalletDoesNotExist::class);

        ($this->handler)(new AddPoints(WalletId::generate()->value, 10));
    }

    #[Test]
    public function it_does_not_leak_the_domain_exception(): void
    {
        try {
            ($this->handler)(new AddPoints(WalletId::generate()->value, 10));
            self::fail('Expected the handler to reject an unknown wallet.');
        } catch (\Throwable $exception) {
            self::assertNotInstanceOf(
                WalletNotFound::class,
                $exception,
                'Domain exceptions must not cross the application boundary.',
            );
        }
    }

    #[Test]
    public function it_rejects_a_non_positive_amount(): void
    {
        $walletId = WalletId::generate();
        $this->wallets->save(WalletBuilder::create()->withId($walletId)->build());

        $this->expectException(InvalidArgumentException::class);

        ($this->handler)(new AddPoints($walletId->value, 0));
    }
}
