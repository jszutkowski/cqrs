<?php

declare(strict_types=1);

namespace App\Infrastructure\System;

use App\Application\System\Command;
use App\Application\System\CommandBusInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class CommandBus implements CommandBusInterface
{
    private MessageBusInterface $messageBus;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function dispatch(Command $command): void
    {
        $this->messageBus->dispatch($command);
    }
}
