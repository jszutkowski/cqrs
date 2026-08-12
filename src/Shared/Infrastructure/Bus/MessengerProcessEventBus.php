<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

use App\Shared\Application\Bus\MessageBus;
use App\Shared\Application\Event\ProcessEvent;
use App\Shared\Application\Event\ProcessEventBus;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;

final readonly class MessengerProcessEventBus implements ProcessEventBus
{
    public function __construct(
        #[Target(MessageBus::EVENT)]
        private MessageBusInterface $messageBus,
    ) {
    }

    /**
     * Held back until the current command's transaction commits, for the same
     * reason domain events are: the process manager must not react to an
     * outcome that a later failure undoes.
     */
    public function publish(ProcessEvent $event): void
    {
        $this->messageBus->dispatch(
            (new Envelope($event))->with(new DispatchAfterCurrentBusStamp()),
        );
    }
}
