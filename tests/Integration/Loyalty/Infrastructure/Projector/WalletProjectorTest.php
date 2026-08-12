<?php

declare(strict_types=1);

namespace App\Tests\Integration\Loyalty\Infrastructure\Projector;

use App\Loyalty\Application\ReadModel\WalletReadModel;
use App\Loyalty\Domain\Transfer\TransferId;
use App\Loyalty\Domain\Wallet\Event\PointsAdded;
use App\Loyalty\Domain\Wallet\Event\PointsRefunded;
use App\Loyalty\Domain\Wallet\Event\PointsWithdrawn;
use App\Loyalty\Domain\Wallet\Event\WalletCreated;
use App\Loyalty\Domain\Wallet\WalletId;
use App\Loyalty\Infrastructure\Projector\WalletProjector;
use App\Shared\Application\Notification\Notifier;
use App\Tests\Integration\IntegrationTestCase;
use App\Tests\Support\Shared\Stub\RecordingNotifier;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(WalletProjector::class)]
final class WalletProjectorTest extends IntegrationTestCase
{
    private WalletProjector $projector;
    private RecordingNotifier $notifier;
    private WalletReadModel $walletReadModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->notifier = new RecordingNotifier();
        $this->projector = new WalletProjector($this->connection, $this->notifier);

        $walletReadModel = self::getContainer()->get(WalletReadModel::class);
        \assert($walletReadModel instanceof WalletReadModel);
        $this->walletReadModel = $walletReadModel;
    }

    #[Test]
    public function it_creates_a_wallet_row_with_a_zero_balance(): void
    {
        $walletId = WalletId::generate();

        $this->projector->projectWalletCreated(new WalletCreated($walletId->value, $this->now()));

        $wallet = $this->walletReadModel->findWallet($walletId->value);

        self::assertNotNull($wallet);
        self::assertSame(0, $wallet->balance);
        self::assertSame([], $wallet->history);
    }

    #[Test]
    public function it_tracks_the_balance_across_the_full_transfer_lifecycle(): void
    {
        $walletId = WalletId::generate();
        $transferId = TransferId::generate();

        $this->projector->projectWalletCreated(new WalletCreated($walletId->value, $this->now()));
        $this->projector->projectPointsAdded(new PointsAdded($walletId->value, 300, null, $this->now()));
        $this->projector->projectPointsWithdrawn(new PointsWithdrawn($walletId->value, 120, $transferId->value, $this->now()));
        $this->projector->projectPointsRefunded(new PointsRefunded($walletId->value, 120, $transferId->value, $this->now()));

        $wallet = $this->walletReadModel->findWallet($walletId->value);

        self::assertNotNull($wallet);
        self::assertSame(300, $wallet->balance);
        self::assertCount(3, $wallet->history);
    }

    #[Test]
    public function it_records_a_withdrawal_as_a_negative_history_entry(): void
    {
        $walletId = WalletId::generate();
        $transferId = TransferId::generate();

        $this->projector->projectWalletCreated(new WalletCreated($walletId->value, $this->now()));
        $this->projector->projectPointsWithdrawn(new PointsWithdrawn($walletId->value, 40, $transferId->value, $this->now()));

        $wallet = $this->walletReadModel->findWallet($walletId->value);

        self::assertNotNull($wallet);
        self::assertSame(-40, $wallet->balance);
        self::assertSame(-40, $wallet->history[0]->amount);
        self::assertSame($transferId->value, $wallet->history[0]->transferId);
    }

    #[Test]
    public function it_survives_a_redelivered_creation_event(): void
    {
        $walletId = WalletId::generate();
        $event = new WalletCreated($walletId->value, $this->now());

        $this->projector->projectWalletCreated($event);
        $this->projector->projectWalletCreated($event);

        $wallets = $this->walletReadModel->listWallets();

        self::assertCount(1, $wallets, 'At-least-once delivery must not duplicate the wallet row.');
    }

    #[Test]
    public function it_pushes_the_balance_it_just_wrote(): void
    {
        $walletId = WalletId::generate();

        $this->projector->projectWalletCreated(new WalletCreated($walletId->value, $this->now()));
        $this->projector->projectPointsAdded(new PointsAdded($walletId->value, 90, null, $this->now()));

        $notifications = $this->notifier->notifications();
        $last = end($notifications);

        self::assertNotFalse($last);
        self::assertSame(90, $last->payload['balance']);
    }

    #[Test]
    public function it_lists_every_projected_wallet(): void
    {
        foreach ([WalletId::generate(), WalletId::generate(), WalletId::generate()] as $walletId) {
            $this->projector->projectWalletCreated(new WalletCreated($walletId->value, $this->now()));
        }

        self::assertCount(3, $this->walletReadModel->listWallets());
    }

    #[Test]
    public function it_returns_null_for_a_wallet_that_was_never_projected(): void
    {
        self::assertNull($this->walletReadModel->findWallet(WalletId::generate()->value));
    }

    #[Test]
    public function it_is_wired_to_the_real_notifier_in_the_container(): void
    {
        self::assertInstanceOf(Notifier::class, self::getContainer()->get(Notifier::class));
    }

    private function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2026-01-01 12:00:00');
    }
}
