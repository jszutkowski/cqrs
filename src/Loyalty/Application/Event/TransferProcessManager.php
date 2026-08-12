<?php

declare(strict_types=1);

namespace App\Loyalty\Application\Event;

use App\Loyalty\Application\Command\CompleteTransfer;
use App\Loyalty\Application\Command\DepositPoints;
use App\Loyalty\Application\Command\FailTransfer;
use App\Loyalty\Application\Command\RefundPoints;
use App\Loyalty\Application\Command\WithdrawPoints;
use App\Loyalty\Application\Exception\TransferDoesNotExist;
use App\Loyalty\Domain\Transfer\Event\TransferInitiated;
use App\Loyalty\Domain\Transfer\Exception\TransferNotFound;
use App\Loyalty\Domain\Transfer\TransferId;
use App\Loyalty\Domain\Transfer\Transfers;
use App\Loyalty\Domain\Wallet\Event\PointsAdded;
use App\Loyalty\Domain\Wallet\Event\PointsWithdrawn;
use App\Shared\Application\Bus\MessageBus;
use App\Shared\Application\Command\CommandBus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Drives a points transfer across two wallet aggregates.
 *
 * Every transition of the process lives here, success and failure alike, so the
 * whole thing reads top to bottom in one file:
 *
 *   TransferInitiated       -> WithdrawPoints  (source wallet)
 *   PointsWithdrawn         -> DepositPoints   (target wallet)
 *   PointsAdded             -> CompleteTransfer
 *   TransferDebitRejected   -> FailTransfer
 *   TransferCreditRejected  -> RefundPoints + FailTransfer   (compensation)
 *
 * The wallets know nothing about transfers and the command handlers decide
 * nothing about the process: they perform their operation and report how it
 * went. That split is what keeps WithdrawPoints reusable outside a transfer.
 *
 * There is no distributed transaction. Each step is a separate command with its
 * own local transaction, and the failure paths compensate rather than roll
 * back — eventual consistency plus compensation, in exchange for aggregates
 * that stay independently consistent.
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

    #[AsMessageHandler(bus: MessageBus::EVENT)]
    public function onTransferInitiated(TransferInitiated $event): void
    {
        $this->commandBus->dispatch(new WithdrawPoints(
            $event->sourceWalletId,
            $event->points,
            $event->aggregateId,
        ));
    }

    #[AsMessageHandler(bus: MessageBus::EVENT)]
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

    #[AsMessageHandler(bus: MessageBus::EVENT)]
    public function onPointsAdded(PointsAdded $event): void
    {
        if (null === $event->transferId) {
            return;
        }

        $this->commandBus->dispatch(new CompleteTransfer($event->transferId));
    }

    #[AsMessageHandler(bus: MessageBus::EVENT)]
    public function onTransferDebitRejected(TransferDebitRejected $event): void
    {
        // Nothing left the source wallet, so there is nothing to compensate.
        $this->commandBus->dispatch(new FailTransfer($event->transferId, $event->reason));
    }

    #[AsMessageHandler(bus: MessageBus::EVENT)]
    public function onTransferCreditRejected(TransferCreditRejected $event): void
    {
        // The debit already happened: put the points back before settling, and
        // in that order — a transfer marked failed while the money is still
        // missing is the one state a reader must never observe.
        $this->commandBus->dispatch(new RefundPoints(
            $event->sourceWalletId,
            $event->points,
            $event->transferId,
        ));

        $this->commandBus->dispatch(new FailTransfer($event->transferId, $event->reason));
    }
}
