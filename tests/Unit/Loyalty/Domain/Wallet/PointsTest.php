<?php

declare(strict_types=1);

namespace App\Tests\Unit\Loyalty\Domain\Wallet;

use App\Loyalty\Domain\Wallet\Points;
use Assert\InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Points::class)]
final class PointsTest extends TestCase
{
    #[Test]
    public function it_holds_a_positive_amount(): void
    {
        self::assertSame(25, Points::of(25)->amount);
    }

    #[Test]
    #[DataProvider('nonPositiveAmounts')]
    public function it_rejects_a_non_positive_amount(int $amount): void
    {
        $this->expectException(InvalidArgumentException::class);

        Points::of($amount);
    }

    /**
     * @return iterable<string, array{amount: int}>
     */
    public static function nonPositiveAmounts(): iterable
    {
        yield 'zero' => ['amount' => 0];
        yield 'negative' => ['amount' => -1];
        yield 'large negative' => ['amount' => -5000];
    }

    #[Test]
    public function it_compares_by_value(): void
    {
        self::assertTrue(Points::of(10)->equals(Points::of(10)));
        self::assertFalse(Points::of(10)->equals(Points::of(11)));
    }
}
