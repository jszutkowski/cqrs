<?php

declare(strict_types=1);

namespace App\Shared\Application\Event;

/**
 * Kept separate from EventBus rather than added as a second method on it:
 * the two carry different kinds of message with different guarantees — domain
 * events are persisted before they are published, process events are not
 * persisted at all — and a caller usually needs exactly one of them.
 */
interface ProcessEventBus
{
    public function publish(ProcessEvent $event): void;
}
