<?php

declare(strict_types=1);

namespace App\Shared\Application\Notification;

final readonly class Notification
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public string $channel,
        public string $name,
        public array $payload,
    ) {
    }
}
