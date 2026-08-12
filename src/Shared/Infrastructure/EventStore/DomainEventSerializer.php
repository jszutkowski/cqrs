<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventStore;

use App\Shared\Domain\EventSourcing\DomainEvent;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final readonly class DomainEventSerializer
{
    public function __construct(
        private NormalizerInterface $normalizer,
        private DenormalizerInterface $denormalizer,
        private EventNameRegistry $eventNameRegistry,
    ) {
    }

    /**
     * @return array{name: string, payload: string}
     */
    public function serialize(DomainEvent $event): array
    {
        $payload = $this->normalizer->normalize($event, 'json');

        return [
            'name' => $this->eventNameRegistry->nameOf($event),
            'payload' => json_encode($payload, \JSON_THROW_ON_ERROR),
        ];
    }

    public function deserialize(string $name, string $payload): DomainEvent
    {
        $data = json_decode($payload, true, 512, \JSON_THROW_ON_ERROR);

        $event = $this->denormalizer->denormalize(
            $data,
            $this->eventNameRegistry->classOf($name),
            'json',
        );

        \assert($event instanceof DomainEvent);

        return $event;
    }
}
