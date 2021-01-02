<?php

declare(strict_types=1);

namespace App\Infrastructure\EventStore;

use App\Application\System\EventStreamBusInterface;
use App\Domain\EventSourcing\DomainEventsStream;
use App\Domain\EventSourcing\EventStoreInterface;
use App\Domain\EventSourcing\NoResultException;
use App\Domain\Loyalty\Events\PointsAdded;
use App\Domain\Loyalty\Events\WalletCreated;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class MySqlEventStore implements EventStoreInterface
{
    private Connection $connection;
    private EventStreamBusInterface $eventStreamBus;
    private SerializerInterface $serializer;
    private string $tableName;

    public function __construct(Connection $connection,
                                EventStreamBusInterface $eventStreamBus,
                                SerializerInterface $serializer,
                                string $tableName)
    {
        $this->connection = $connection;
        $this->eventStreamBus = $eventStreamBus;
        $this->serializer = $serializer;
        $this->tableName = $tableName;
    }

    /**
     * @throws NoResultException
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     * @throws \ReflectionException
     */
    public function get(string $aggregateId): DomainEventsStream
    {
        $stmt = $this->connection->executeQuery(<<<SQL
            SELECT
                aggregate_id,
                event_name,
                payload
            FROM {$this->tableName}
            WHERE aggregate_id = :aggregateId
            ORDER BY created_at ASC
        SQL, [
            'aggregateId' => $aggregateId,
        ]);

        $events = [];

        while ($row = $stmt->fetchAssociative()) {
            $events[] = $this->serializer->deserialize(
                $row['payload'],
                $row['event_name'],
                'json',
                [
                    AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS => [
                        WalletCreated::class => ['walletId' => $aggregateId],
                        PointsAdded::class => ['walletId' => $aggregateId],
                    ],
                ]
            );
        }

        if (empty($events)) {
            throw new NoResultException('No result found');
        }

        return new DomainEventsStream($events);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function store(string $id, DomainEventsStream $events): void
    {
        foreach ($events as $event) {
            $this->connection->insert($this->tableName, [
                'aggregate_id' => $id,
                'event_name' => get_class($event),
                'version' => $event->getVersion(),
                'payload' => $this->serializer->serialize($event, 'json'),
                'created_at' => $event->getCreatedAt()->format('Y-m-d H:i:s'),
            ]);
        }

        $this->eventStreamBus->dispatch((new Envelope($events))
            ->with(new DispatchAfterCurrentBusStamp())
        );
    }
}
