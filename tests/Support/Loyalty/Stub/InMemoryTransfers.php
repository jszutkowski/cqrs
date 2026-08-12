<?php

declare(strict_types=1);

namespace App\Tests\Support\Loyalty\Stub;

use App\Loyalty\Domain\Transfer\Exception\TransferNotFound;
use App\Loyalty\Domain\Transfer\Transfer;
use App\Loyalty\Domain\Transfer\TransferId;
use App\Loyalty\Domain\Transfer\Transfers;
use App\Shared\Application\Event\EventBus;
use App\Shared\Domain\EventSourcing\DomainEvent;
use App\Shared\Domain\EventSourcing\DomainEventsStream;

final class InMemoryTransfers implements Transfers
{
    /**
     * @var array<string, list<DomainEvent>>
     */
    private array $streams = [];

    public function __construct(
        private readonly ?EventBus $eventBus = null,
    ) {
    }

    public function get(TransferId $transferId): Transfer
    {
        $events = $this->streams[$transferId->value] ?? throw TransferNotFound::withId($transferId);

        return Transfer::reconstitute(DomainEventsStream::fromArray($events));
    }

    public function save(Transfer $transfer): void
    {
        $events = $transfer->popRecordedEvents();

        foreach ($events as $event) {
            $this->streams[$transfer->aggregateId()][] = $event;
        }

        $this->eventBus?->publish(...$events->toArray());
    }
}
