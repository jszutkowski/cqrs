<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Dbal;

final class MalformedRow extends \RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function missingColumn(string $column): self
    {
        return new self(\sprintf('Column "%s" is absent from the result row.', $column));
    }

    public static function unexpectedNull(string $column): self
    {
        return new self(\sprintf('Column "%s" is null where a value is required.', $column));
    }

    public static function notScalar(string $column, string $actualType): self
    {
        return new self(\sprintf('Column "%s" holds %s, which cannot be read as a string.', $column, $actualType));
    }

    public static function notNumeric(string $column, string $actualType): self
    {
        return new self(\sprintf('Column "%s" holds %s, which cannot be read as an integer.', $column, $actualType));
    }
}
