<?php

declare(strict_types=1);

namespace App\Loyalty\Domain\Wallet;

use Assert\Assertion;

/**
 * A positive amount of loyalty points taking part in a single operation.
 * A wallet balance can be zero, but adding or withdrawing zero points is
 * meaningless — hence the strict "greater than zero" invariant.
 */
final readonly class Points
{
    private function __construct(
        public int $amount,
    ) {
    }

    public static function of(int $amount): self
    {
        Assertion::greaterThan($amount, 0, 'Points amount must be greater than zero.');

        return new self($amount);
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount;
    }
}
