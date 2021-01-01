<?php
declare(strict_types=1);

namespace App\Infrastructure\EventStore;


use App\Domain\EventSourcing\AggregateRootInterface;
use App\Domain\EventSourcing\DomainEventsStream;
use App\Domain\EventSourcing\NoResultException;
use Doctrine\DBAL\Connection;
use Symfony\Component\Serializer\SerializerInterface;
use Webmozart\Assert\Assert;

class SqliteEventStore implements EventStoreInterface
{
    private Connection $connection;
    private SerializerInterface $serializer;
    private string $aggregateClass;

    public function __construct(Connection $connection, SerializerInterface $serializer, string $aggregateClass)
    {
        $this->connection = $connection;
        $this->serializer = $serializer;
        $this->aggregateClass = $aggregateClass;
    }

    /**
     * @param string $id
     * @return AggregateRootInterface
     * @throws NoResultException
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     * @throws \ReflectionException
     */
    public function get(string $id): AggregateRootInterface
    {
        $stmt = $this->connection->executeQuery(<<<SQL
            SELECT
                aggregate_class,
                aggregate_id,
                event_name,
                payload
            FROM event_store
            WHERE aggregate_class = :aggregateClass AND aggregate_id = :aggregateId
            ORDER BY created_at ASC
        SQL, [
            'aggregateClass' => $this->aggregateClass,
            'aggregateId' => $id
        ]);

        $events = [];

        while ($row = $stmt->fetchAssociative()) {
            $events[] = $this->serializer->deserialize($row, $row['event_name'], 'json');
        }

        if (empty($events)) {
            throw new NoResultException('No result found');
        }

        $class = new \ReflectionClass($this->aggregateClass);

        /** @var $aggregateRoot AggregateRootInterface */
        $aggregateRoot = $class->newInstanceWithoutConstructor();

        $aggregateRoot->initializeState(new DomainEventsStream($events));

        return $aggregateRoot;
    }

    /**
     * @param AggregateRootInterface $aggregateRoot
     * @throws \Doctrine\DBAL\Exception
     */
    public function store(AggregateRootInterface $aggregateRoot): void
    {
        Assert::isInstanceOf($aggregateRoot, $this->aggregateClass);

        $events = $aggregateRoot->getUncommittedEvents();

        foreach ($events as $event) {
            $this->connection->insert('event_store', [
                'aggregate_class' => $this->aggregateClass,
                'aggregate_id'=> $aggregateRoot->getAggregateRootId(),
                'event_name' => get_class($event),
                'version' => $event->getVersion(),
                'payload' => $this->serializer->serialize($event, 'json'),
                'created_at' => $event->getCreatedAt()->format('U')
            ]);
        }
    }
}
