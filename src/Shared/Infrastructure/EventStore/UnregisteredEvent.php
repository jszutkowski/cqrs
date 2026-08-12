<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventStore;

final class UnregisteredEvent extends \LogicException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function forClass(string $eventClass): self
    {
        return new self(\sprintf(
            'Event "%s" has no stream name; register it in the event name map.',
            $eventClass,
        ));
    }

    public static function forName(string $name): self
    {
        return new self(\sprintf(
            'Stream name "%s" maps to no known event class; the stream may be newer than this code.',
            $name,
        ));
    }
}
