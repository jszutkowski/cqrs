<?php

declare(strict_types=1);

namespace App\Shared\Application\Notification;

/**
 * Pushes read-side updates to connected clients. Best effort by contract:
 * implementations must not fail the surrounding use case when the transport
 * is unavailable — a missed live update is repaired by the next page load.
 */
interface Notifier
{
    public function notify(Notification $notification): void;
}
