<?php

declare(strict_types=1);

namespace App\Tests\Unit\Loyalty\Domain\Transfer;

use App\Loyalty\Domain\Transfer\Event\TransferCompleted;
use App\Loyalty\Domain\Transfer\Event\TransferFailed;
use App\Loyalty\Domain\Transfer\Event\TransferInitiated;
use App\Loyalty\Domain\Transfer\Exception\InvalidTransfer;
use App\Loyalty\Domain\Transfer\Exception\TransferAlreadySettled;
use App\Loyalty\Domain\Transfer\Transfer;
use App\Loyalty\Domain\Transfer\TransferId;
use App\Loyalty\Domain\Transfer\TransferStatus;
use App\Loyalty\Domain\Wallet\Points;
use App\Loyalty\Domain\Wallet\WalletId;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Transfer::class)]
final class TransferTest extends TestCase
{
    #[Test]
    public function it_records_transfer_initiated_with_both_wallets(): void
    {
        $transferId = TransferId::generate();
        $sourceWalletId = WalletId::generate();
        $targetWalletId = WalletId::generate();

        $transfer = Transfer::initiate($transferId, $sourceWalletId, $targetWalletId, Points::of(75), $this->now());

        $events = $transfer->popRecordedEvents()->toArray();

        self::assertCount(1, $events);
        self::assertInstanceOf(TransferInitiated::class, $events[0]);
        self::assertSame($transferId->value, $events[0]->aggregateId);
        self::assertSame($sourceWalletId->value, $events[0]->sourceWalletId);
        self::assertSame($targetWalletId->value, $events[0]->targetWalletId);
        self::assertSame(75, $events[0]->points);
        self::assertSame(TransferStatus::Initiated, $transfer->status());
    }

    #[Test]
    public function it_rejects_a_transfer_to_the_same_wallet(): void
    {
        $walletId = WalletId::generate();

        $this->expectException(InvalidTransfer::class);

        Transfer::initiate(TransferId::generate(), $walletId, $walletId, Points::of(10), $this->now());
    }

    #[Test]
    public function it_completes_an_initiated_transfer(): void
    {
        $transfer = $this->initiatedTransfer();

        $transfer->complete($this->now());

        self::assertSame(TransferStatus::Completed, $transfer->status());

        $events = $transfer->popRecordedEvents()->toArray();
        self::assertInstanceOf(TransferCompleted::class, end($events));
    }

    #[Test]
    public function it_fails_an_initiated_transfer_with_a_reason(): void
    {
        $transfer = $this->initiatedTransfer();

        $transfer->fail('Insufficient points', $this->now());

        self::assertSame(TransferStatus::Failed, $transfer->status());

        $events = $transfer->popRecordedEvents()->toArray();
        $failed = end($events);

        self::assertInstanceOf(TransferFailed::class, $failed);
        self::assertSame('Insufficient points', $failed->reason);
    }

    #[Test]
    public function it_refuses_to_complete_a_failed_transfer(): void
    {
        $transfer = $this->initiatedTransfer();
        $transfer->fail('Insufficient points', $this->now());

        $this->expectException(TransferAlreadySettled::class);

        $transfer->complete($this->now());
    }

    #[Test]
    public function it_refuses_to_fail_a_completed_transfer(): void
    {
        $transfer = $this->initiatedTransfer();
        $transfer->complete($this->now());

        $this->expectException(TransferAlreadySettled::class);

        $transfer->fail('Too late', $this->now());
    }

    #[Test]
    public function it_rebuilds_its_status_from_the_event_stream(): void
    {
        $transfer = $this->initiatedTransfer();
        $transfer->complete($this->now());

        $rebuilt = Transfer::reconstitute($transfer->popRecordedEvents());

        self::assertSame(TransferStatus::Completed, $rebuilt->status());
        self::assertSame(1, $rebuilt->committedVersion());
    }

    private function initiatedTransfer(): Transfer
    {
        return Transfer::initiate(
            TransferId::generate(),
            WalletId::generate(),
            WalletId::generate(),
            Points::of(50),
            $this->now(),
        );
    }

    private function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2026-01-01 12:00:00');
    }
}
