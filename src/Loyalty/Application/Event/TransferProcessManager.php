<?php

declare(strict_types=1);

namespace App\Loyalty\Application\Event;

use App\Loyalty\Application\Command\CompleteTransfer;
use App\Loyalty\Application\Command\DepositPoints;
use App\Loyalty\Application\Command\WithdrawPoints;
use App\Loyalty\Application\Exception\TransferDoesNotExist;
use App\Loyalty\Domain\Transfer\Event\TransferInitiated;
use App\Loyalty\Domain\Transfer\Exception\TransferNotFound;
use App\Loyalty\Domain\Transfer\TransferId;
use App\Loyalty\Domain\Transfer\Transfers;
use App\Loyalty\Domain\Wallet\Event\PointsAdded;
use App\Loyalty\Domain\Wallet\Event\PointsWithdrawn;
use App\Shared\Application\Command\CommandBus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Drives a points transfer across two wallet aggregates.
 *
 * Neither wallet knows about the transfer, and the Transfer aggregate moves no
 * points — this process manager is what turns three independent aggregates into
 * one business operation:
 *
 *   TransferInitiated  -> WithdrawPoints (source wallet)
 *   PointsWithdrawn    -> DepositPoints  (target wallet)
 *   PointsAdded        -> CompleteTransfer
 *
 * There is no distributed transaction. Each step is a separate command with its
 * own local transaction, and the failure paths compensate instead of rolling
 * back: WithdrawPointsHandler settles the transfer as failed if the source has
 * too few points, and DepositPointsHandler refunds the source if the target
 * wallet turns out to be gone. That is the trade the saga pattern makes —
 * eventual consistency plus compensation, in exchange for aggregates that stay
 * independently consistent.
 *
 * Wallet events unrelated to a transfer carry a null transfer id and are ignored
 * here, which also makes redelivery of a plain top-up harmless.
 */
final readonly class TransferProcessManager
{
    public function __construct(
        private Transfers $transfers,
        private CommandBus $commandBus,
    ) {
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onTransferInitiated(TransferInitiated $event): void
    {
        $this->commandBus->dispatch(new WithdrawPoints(
            $event->sourceWalletId,
            $event->points,
            $event->aggregateId,
        ));
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onPointsWithdrawn(PointsWithdrawn $event): void
    {
        $transferId = TransferId::fromString($event->transferId);

        try {
            $transfer = $this->transfers->get($transferId);
        } catch (TransferNotFound) {
            // The debit happened, but the transfer it belongs to is unknown —
            // there is nothing sensible to compensate towards, so let the
            // message fail into the failure transport rather than guess.
            throw TransferDoesNotExist::withId($event->transferId);
        }

        $this->commandBus->dispatch(new DepositPoints(
            $transfer->targetWalletId()->value,
            $event->aggregateId,
            $event->points,
            $event->transferId,
        ));
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onPointsAdded(PointsAdded $event): void
    {
        if (null === $event->transferId) {
            return;
        }

        $this->commandBus->dispatch(new CompleteTransfer($event->transferId));
    }
}
