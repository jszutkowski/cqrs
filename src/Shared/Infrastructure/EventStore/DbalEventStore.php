<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventStore;

use App\Shared\Domain\EventSourcing\DomainEvent;
use App\Shared\Domain\EventSourcing\DomainEventsStream;
use App\Shared\Domain\EventSourcing\EventStore;
use App\Shared\Domain\EventSourcing\Exception\ConcurrencyConflict;
use App\Shared\Infrastructure\Dbal\Row;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

final readonly class DbalEventStore implements EventStore
{
    public function __construct(
        private Connection $connection,
        private DomainEventSerializer $eventSerializer,
        private string $tableName,
    ) {
    }

    public function append(string $aggregateType, string $aggregateId, DomainEventsStream $events, int $expectedVersion): void
    {
        if ($events->isEmpty()) {
            return;
        }

        $version = $expectedVersion;

        try {
            foreach ($events as $event) {
                ++$version;

                $serialized = $this->eventSerializer->serialize($event);

                $this->connection->insert($this->tableName, [
                    'aggregate_type' => $aggregateType,
                    'aggregate_id' => $aggregateId,
                    'version' => $version,
                    'event_name' => $serialized['name'],
                    'payload' => $serialized['payload'],
                    'occurred_at' => $event->occurredAt->format('Y-m-d H:i:s.u'),
                ]);
            }
        } catch (UniqueConstraintViolationException) {
            // The unique index on (aggregate_type, aggregate_id, version) is the
            // concurrency control: another writer already claimed this version,
            // so the aggregate this write was based on is stale.
            throw ConcurrencyConflict::forAggregate($aggregateId, $expectedVersion);
        }
    }

    public function load(string $aggregateType, string $aggregateId): DomainEventsStream
    {
        $rows = $this->connection->fetchAllAssociative(
            <<<SQL
                SELECT event_name, payload
                FROM {$this->tableName}
                WHERE aggregate_type = :aggregateType AND aggregate_id = :aggregateId
                ORDER BY version ASC
                SQL,
            ['aggregateType' => $aggregateType, 'aggregateId' => $aggregateId],
        );

        $events = array_map(
            function (array $row): DomainEvent {
                $row = Row::of($row);

                return $this->eventSerializer->deserialize($row->string('event_name'), $row->string('payload'));
            },
            $rows,
        );

        return DomainEventsStream::fromArray($events);
    }
}
