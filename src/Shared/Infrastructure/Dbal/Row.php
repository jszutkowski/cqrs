<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Dbal;

/**
 * Reads typed values out of a DBAL result row.
 *
 * DBAL hands back array<string, mixed>, so every read site would otherwise cast
 * blindly. Casting is not the problem — casting *silently* is: a renamed column
 * arrives as null and turns into 0 or an empty string, and the projection looks
 * healthy while producing wrong numbers. These readers fail instead.
 */
final readonly class Row
{
    /**
     * @param array<string, mixed> $values
     */
    private function __construct(
        private array $values,
    ) {
    }

    /**
     * @param array<string, mixed> $values
     */
    public static function of(array $values): self
    {
        return new self($values);
    }

    public function string(string $column): string
    {
        $value = $this->require($column);

        if (!\is_scalar($value)) {
            throw MalformedRow::notScalar($column, get_debug_type($value));
        }

        return (string) $value;
    }

    public function int(string $column): int
    {
        $value = $this->require($column);

        if (!is_numeric($value)) {
            throw MalformedRow::notNumeric($column, get_debug_type($value));
        }

        return (int) $value;
    }

    public function nullableString(string $column): ?string
    {
        if (!\array_key_exists($column, $this->values) || null === $this->values[$column]) {
            return null;
        }

        return $this->string($column);
    }

    private function require(string $column): mixed
    {
        if (!\array_key_exists($column, $this->values)) {
            throw MalformedRow::missingColumn($column);
        }

        return $this->values[$column] ?? throw MalformedRow::unexpectedNull($column);
    }
}
