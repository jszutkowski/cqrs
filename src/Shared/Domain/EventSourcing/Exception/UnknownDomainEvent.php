<?php

declare(strict_types=1);

namespace App\Shared\Domain\EventSourcing\Exception;

final class UnknownDomainEvent extends \LogicException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function inAggregate(string $eventClass, string $aggregateClass): self
    {
        return new self(\sprintf(
            'Aggregate "%s" does not know how to apply event "%s".',
            $aggregateClass,
            $eventClass,
        ));
    }
}
