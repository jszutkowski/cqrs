<?php

declare(strict_types=1);

namespace App\Loyalty\Application\Command;

use App\Loyalty\Application\Exception\TransferDoesNotExist;
use App\Loyalty\Domain\Transfer\Exception\TransferAlreadySettled;
use App\Loyalty\Domain\Transfer\Exception\TransferNotFound;
use App\Loyalty\Domain\Transfer\TransferId;
use App\Loyalty\Domain\Transfer\Transfers;
use Psr\Clock\ClockInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CompleteTransferHandler
{
    public function __construct(
        private Transfers $transfers,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(CompleteTransfer $command): void
    {
        try {
            $transfer = $this->transfers->get(TransferId::fromString($command->transferId));
            $transfer->complete($this->clock->now());
        } catch (TransferNotFound) {
            throw TransferDoesNotExist::withId($command->transferId);
        } catch (TransferAlreadySettled) {
            // At-least-once delivery: a redelivered settle command after the
            // transfer reached a terminal state is a no-op, not an error.
            return;
        }

        $this->transfers->save($transfer);
    }
}
