<?php

declare(strict_types=1);

namespace App\Application\Notifier;

class Event
{
    public string $channel;
    public string $eventName;
    public array $payload;

    public function __construct(string $channel, string $eventName, array $payload)
    {
        $this->channel = $channel;
        $this->eventName = $eventName;
        $this->payload = $payload;
    }
}
