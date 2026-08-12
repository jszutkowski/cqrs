<?php

declare(strict_types=1);

namespace App\Tests\Unit\Loyalty\Application\Event;

use App\Loyalty\Application\Command\CompleteTransfer;
use App\Loyalty\Application\Command\DepositPoints;
use App\Loyalty\Application\Command\FailTransfer;
use App\Loyalty\Application\Command\RefundPoints;
use App\Loyalty\Application\Command\WithdrawPoints;
use App\Loyalty\Application\Event\TransferProcessManager;
use App\Loyalty\Application\Exception\TransferDoesNotExist;
use App\Loyalty\Domain\Transfer\Event\TransferInitiated;
use App\Loyalty\Domain\Transfer\TransferId;
use App\Loyalty\Domain\Transfer\TransferStatus;
use App\Loyalty\Domain\Wallet\Event\PointsAdded;
use App\Loyalty\Domain\Wallet\Event\PointsWithdrawn;
use App\Loyalty\Domain\Wallet\WalletId;
use App\Tests\Support\Loyalty\Stub\InMemoryTransfers;
use App\Tests\Support\Loyalty\TransferSagaHarness;
use App\Tests\Support\Shared\Stub\RecordingCommandBus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TransferProcessManager::class)]
final class TransferProcessManagerTest extends TestCase
{
    #[Test]
    public function it_moves_points_between_wallets_when_every_step_succeeds(): void
    {
        $harness = new TransferSagaHarness();
        $source = $harness->createWallet(initialPoints: 500);
        $target = $harness->createWallet(initialPoints: 100);

        $transferId = $harness->initiateTransfer($source, $target, 200);

        self::assertSame(300, $harness->balanceOf($source));
        self::assertSame(300, $harness->balanceOf($target));
        self::assertSame(TransferStatus::Completed, $harness->transfer($transferId)->status());
    }

    #[Test]
    public function it_conserves_the_total_number_of_points(): void
    {
        $harness = new TransferSagaHarness();
        $source = $harness->createWallet(initialPoints: 500);
        $target = $harness->createWallet(initialPoints: 100);

        $harness->initiateTransfer($source, $target, 200);

        self::assertSame(600, $harness->balanceOf($source) + $harness->balanceOf($target));
    }

    #[Test]
    public function it_fails_the_transfer_without_moving_points_when_the_source_is_short(): void
    {
        $harness = new TransferSagaHarness();
        $source = $harness->createWallet(initialPoints: 50);
        $target = $harness->createWallet(initialPoints: 10);

        $transferId = $harness->initiateTransfer($source, $target, 200);

        self::assertSame(50, $harness->balanceOf($source), 'A rejected debit must not touch the source.');
        self::assertSame(10, $harness->balanceOf($target), 'Nothing may reach the target.');
        self::assertSame(TransferStatus::Failed, $harness->transfer($transferId)->status());
        self::assertFalse($harness->hasHandled(DepositPoints::class), 'The credit leg must never start.');
    }

    #[Test]
    public function it_refunds_the_source_when_the_target_wallet_is_missing(): void
    {
        $harness = new TransferSagaHarness();
        $source = $harness->createWallet(initialPoints: 500);
        $missingTarget = WalletId::generate();

        $transferId = $harness->initiateTransfer($source, $missingTarget, 200);

        self::assertSame(500, $harness->balanceOf($source), 'The compensating refund must restore the balance.');
        self::assertSame(TransferStatus::Failed, $harness->transfer($transferId)->status());
        self::assertTrue($harness->hasHandled(RefundPoints::class), 'The compensation must actually run.');
    }

    #[Test]
    public function it_asks_the_source_wallet_for_a_debit_when_a_transfer_starts(): void
    {
        $commandBus = new RecordingCommandBus();
        $processManager = new TransferProcessManager(new InMemoryTransfers(), $commandBus);
        $transferId = TransferId::generate();
        $source = WalletId::generate();

        $processManager->onTransferInitiated(new TransferInitiated(
            $transferId->value,
            $source->value,
            WalletId::generate()->value,
            120,
            $this->now(),
        ));

        $withdraw = $commandBus->firstOf(WithdrawPoints::class);

        self::assertInstanceOf(WithdrawPoints::class, $withdraw);
        self::assertSame($source->value, $withdraw->walletId);
        self::assertSame(120, $withdraw->points);
        self::assertSame($transferId->value, $withdraw->transferId);
    }

    #[Test]
    public function it_ignores_a_top_up_that_belongs_to_no_transfer(): void
    {
        $commandBus = new RecordingCommandBus();
        $processManager = new TransferProcessManager(new InMemoryTransfers(), $commandBus);

        $processManager->onPointsAdded(new PointsAdded(
            WalletId::generate()->value,
            75,
            null,
            $this->now(),
        ));

        self::assertSame([], $commandBus->dispatchedCommands(), 'A plain top-up must not settle any transfer.');
    }

    #[Test]
    public function it_completes_the_transfer_when_the_credit_leg_lands(): void
    {
        $commandBus = new RecordingCommandBus();
        $processManager = new TransferProcessManager(new InMemoryTransfers(), $commandBus);
        $transferId = TransferId::generate();

        $processManager->onPointsAdded(new PointsAdded(
            WalletId::generate()->value,
            75,
            $transferId->value,
            $this->now(),
        ));

        $complete = $commandBus->firstOf(CompleteTransfer::class);

        self::assertInstanceOf(CompleteTransfer::class, $complete);
        self::assertSame($transferId->value, $complete->transferId);
    }

    #[Test]
    public function it_refuses_to_continue_a_debit_that_belongs_to_an_unknown_transfer(): void
    {
        $commandBus = new RecordingCommandBus();
        $processManager = new TransferProcessManager(new InMemoryTransfers(), $commandBus);

        $this->expectException(TransferDoesNotExist::class);

        $processManager->onPointsWithdrawn(new PointsWithdrawn(
            WalletId::generate()->value,
            75,
            TransferId::generate()->value,
            $this->now(),
        ));
    }

    #[Test]
    public function it_never_settles_a_transfer_twice(): void
    {
        $harness = new TransferSagaHarness();
        $source = $harness->createWallet(initialPoints: 500);
        $target = $harness->createWallet(initialPoints: 0);

        $transferId = $harness->initiateTransfer($source, $target, 100);

        $settlements = array_filter(
            $harness->handledCommands(),
            static fn (object $command): bool => $command instanceof CompleteTransfer || $command instanceof FailTransfer,
        );

        self::assertCount(1, $settlements);
        self::assertSame(TransferStatus::Completed, $harness->transfer($transferId)->status());
    }

    private function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2026-01-01 12:00:00');
    }
}
