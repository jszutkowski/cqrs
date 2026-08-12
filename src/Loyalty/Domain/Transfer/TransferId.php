<?php

declare(strict_types=1);

namespace App\Loyalty\Domain\Transfer;

use Assert\Assertion;
use Symfony\Component\Uid\Uuid;

final readonly class TransferId
{
    private function __construct(
        public string $value,
    ) {
    }

    public static function fromString(string $value): self
    {
        Assertion::uuid($value);

        return new self($value);
    }

    public static function generate(): self
    {
        return new self(Uuid::v4()->toRfc4122());
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
