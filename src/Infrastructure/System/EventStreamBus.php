<?php

declare(strict_types=1);

namespace App\Infrastructure\System;

use App\Application\System\EventStreamBusInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class EventStreamBus implements EventStreamBusInterface
{
    private MessageBusInterface $messageBus;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    /**
     * {@inheritDoc}
     */
    public function dispatch(object $event): void
    {
        $this->messageBus->dispatch($event);
    }
}
