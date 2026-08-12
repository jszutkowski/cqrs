<?php

declare(strict_types=1);

namespace App\Loyalty\Infrastructure\Repository;

use App\Loyalty\Domain\Transfer\Exception\TransferNotFound;
use App\Loyalty\Domain\Transfer\Transfer;
use App\Loyalty\Domain\Transfer\TransferId;
use App\Loyalty\Domain\Transfer\Transfers;
use App\Shared\Application\Event\EventBus;
use App\Shared\Domain\EventSourcing\EventStore;

final readonly class EventSourcedTransfers implements Transfers
{
    public function __construct(
        private EventStore $eventStore,
        private EventBus $eventBus,
    ) {
    }

    public function get(TransferId $transferId): Transfer
    {
        $stream = $this->eventStore->load($transferId->value);

        if ($stream->isEmpty()) {
            throw TransferNotFound::withId($transferId);
        }

        return Transfer::reconstitute($stream);
    }

    public function save(Transfer $transfer): void
    {
        $events = $transfer->popRecordedEvents();

        $this->eventStore->append($transfer->aggregateId(), $events, $transfer->committedVersion());

        $this->eventBus->publish(...$events->toArray());
    }
}
