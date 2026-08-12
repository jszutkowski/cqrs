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
final readonly class FailTransferHandler
{
    public function __construct(
        private Transfers $transfers,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(FailTransfer $command): void
    {
        try {
            $transfer = $this->transfers->get(TransferId::fromString($command->transferId));
            $transfer->fail($command->reason, $this->clock->now());
        } catch (TransferNotFound) {
            throw TransferDoesNotExist::withId($command->transferId);
        } catch (TransferAlreadySettled) {
            // At-least-once delivery: settling an already settled transfer is a no-op.
            return;
        }

        $this->transfers->save($transfer);
    }
}
