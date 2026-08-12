<?php

declare(strict_types=1);

namespace App\Shared\Domain\EventSourcing\Exception;

final class ConcurrencyConflict extends \RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function forAggregate(string $aggregateId, int $expectedVersion): self
    {
        return new self(\sprintf(
            'Aggregate "%s" was modified concurrently: expected version %d is no longer the head of the stream.',
            $aggregateId,
            $expectedVersion,
        ));
    }
}
