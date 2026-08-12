<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

use App\Shared\Application\Command\Command;
use App\Shared\Application\Command\CommandBus;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class MessengerCommandBus implements CommandBus
{
    /**
     * command.bus is the default bus, so the plain MessageBusInterface resolves
     * to it; the event bus is the one that needs an explicit target.
     */
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    public function dispatch(Command $command): void
    {
        $this->messageBus->dispatch($command);
    }
}
