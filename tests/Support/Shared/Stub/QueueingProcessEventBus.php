<?php

declare(strict_types=1);

namespace App\Tests\Support\Shared\Stub;

use App\Shared\Application\Event\ProcessEvent;
use App\Shared\Application\Event\ProcessEventBus;

/**
 * Queues process events instead of delivering them, so a test can drain them in
 * step with the domain events and drive the whole saga deterministically.
 */
final class QueueingProcessEventBus implements ProcessEventBus
{
    /**
     * @var list<ProcessEvent>
     */
    private array $queue = [];

    public function publish(ProcessEvent $event): void
    {
        $this->queue[] = $event;
    }

    public function drain(): ?ProcessEvent
    {
        return array_shift($this->queue);
    }

    public function isEmpty(): bool
    {
        return [] === $this->queue;
    }
}
