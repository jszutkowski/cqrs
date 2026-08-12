<?php

declare(strict_types=1);

namespace App\Loyalty\Domain\Transfer;

use App\Loyalty\Domain\Transfer\Event\TransferCompleted;
use App\Loyalty\Domain\Transfer\Event\TransferFailed;
use App\Loyalty\Domain\Transfer\Event\TransferInitiated;
use App\Loyalty\Domain\Transfer\Exception\InvalidTransfer;
use App\Loyalty\Domain\Transfer\Exception\TransferAlreadySettled;
use App\Loyalty\Domain\Wallet\Points;
use App\Loyalty\Domain\Wallet\WalletId;
use App\Shared\Domain\EventSourcing\AggregateRoot;
use App\Shared\Domain\EventSourcing\DomainEvent;
use App\Shared\Domain\EventSourcing\Exception\UnknownDomainEvent;

/**
 * State of a points transfer between two wallets. The transfer itself moves no
 * points — the wallet aggregates do. This aggregate only tracks how far the
 * process got, and the transfer process manager drives it forward
 * (see App\Loyalty\Application\Event).
 */
final class Transfer extends AggregateRoot
{
    private TransferId $transferId;
    private WalletId $sourceWalletId;
    private WalletId $targetWalletId;
    private TransferStatus $status;

    /**
     * @throws InvalidTransfer
     */
    public static function initiate(
        TransferId $transferId,
        WalletId $sourceWalletId,
        WalletId $targetWalletId,
        Points $points,
        \DateTimeImmutable $occurredAt,
    ): self {
        if ($sourceWalletId->equals($targetWalletId)) {
            throw InvalidTransfer::betweenSameWallet($sourceWalletId);
        }

        $transfer = new self();

        $transfer->recordThat(new TransferInitiated(
            $transferId->value,
            $sourceWalletId->value,
            $targetWalletId->value,
            $points->amount,
            $occurredAt,
        ));

        return $transfer;
    }

    /**
     * @throws TransferAlreadySettled
     */
    public function complete(\DateTimeImmutable $occurredAt): void
    {
        $this->guardNotSettled();

        $this->recordThat(new TransferCompleted($this->transferId->value, $occurredAt));
    }

    /**
     * @throws TransferAlreadySettled
     */
    public function fail(string $reason, \DateTimeImmutable $occurredAt): void
    {
        $this->guardNotSettled();

        $this->recordThat(new TransferFailed($this->transferId->value, $reason, $occurredAt));
    }

    public static function aggregateType(): string
    {
        return 'transfer';
    }

    public function aggregateId(): string
    {
        return $this->transferId->value;
    }

    public function transferId(): TransferId
    {
        return $this->transferId;
    }

    public function sourceWalletId(): WalletId
    {
        return $this->sourceWalletId;
    }

    public function targetWalletId(): WalletId
    {
        return $this->targetWalletId;
    }

    public function status(): TransferStatus
    {
        return $this->status;
    }

    private function guardNotSettled(): void
    {
        if ($this->status->isSettled()) {
            throw TransferAlreadySettled::withStatus($this->transferId, $this->status);
        }
    }

    protected function apply(DomainEvent $event): void
    {
        match (true) {
            $event instanceof TransferInitiated => $this->applyInitiated($event),
            $event instanceof TransferCompleted => $this->status = TransferStatus::Completed,
            $event instanceof TransferFailed => $this->status = TransferStatus::Failed,
            default => throw UnknownDomainEvent::inAggregate($event::class, self::class),
        };
    }

    private function applyInitiated(TransferInitiated $event): void
    {
        $this->transferId = TransferId::fromString($event->aggregateId);
        $this->sourceWalletId = WalletId::fromString($event->sourceWalletId);
        $this->targetWalletId = WalletId::fromString($event->targetWalletId);
        $this->status = TransferStatus::Initiated;
    }
}
