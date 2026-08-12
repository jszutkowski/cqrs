<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

use App\Shared\Application\Event\EventBus;
use App\Shared\Domain\EventSourcing\DomainEvent;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;

final readonly class MessengerEventBus implements EventBus
{
    public function __construct(
        #[Target('event.bus')]
        private MessageBusInterface $messageBus,
    ) {
    }

    /**
     * The stamp holds each event back until the command currently being handled
     * finishes and its transaction commits. Without it a projection could read
     * state that a later failure rolls back.
     */
    public function publish(DomainEvent ...$events): void
    {
        foreach ($events as $event) {
            $this->messageBus->dispatch(
                (new Envelope($event))->with(new DispatchAfterCurrentBusStamp()),
            );
        }
    }
}
