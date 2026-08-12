<?php

declare(strict_types=1);

namespace App\Tests\Support\Shared\Stub;

use App\Shared\Application\Event\EventBus;
use App\Shared\Domain\EventSourcing\DomainEvent;

/**
 * Queues published events instead of delivering them, so a test can drain the
 * queue step by step and drive the saga deterministically.
 */
final class QueueingEventBus implements EventBus
{
    /**
     * @var list<DomainEvent>
     */
    private array $queue = [];

    public function publish(DomainEvent ...$events): void
    {
        foreach ($events as $event) {
            $this->queue[] = $event;
        }
    }

    public function drain(): ?DomainEvent
    {
        return array_shift($this->queue);
    }

    public function isEmpty(): bool
    {
        return [] === $this->queue;
    }
}
