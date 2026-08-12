<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventStore;

use App\Shared\Domain\EventSourcing\DomainEvent;

/**
 * Two-way map between the name stored in the event stream and the PHP class.
 *
 * Storing the FQCN instead would make every namespace change a data migration:
 * an event stream is kept forever, so its schema must not depend on where the
 * class currently lives.
 *
 * Adding an event without registering it here fails loudly on the first write.
 */
final readonly class EventNameRegistry
{
    /**
     * @param array<string, class-string<DomainEvent>> $classesByName
     */
    public function __construct(
        private array $classesByName,
    ) {
    }

    public function nameOf(DomainEvent $event): string
    {
        $name = array_search($event::class, $this->classesByName, true);

        if (!\is_string($name)) {
            throw UnregisteredEvent::forClass($event::class);
        }

        return $name;
    }

    /**
     * @return class-string<DomainEvent>
     */
    public function classOf(string $name): string
    {
        return $this->classesByName[$name] ?? throw UnregisteredEvent::forName($name);
    }
}
