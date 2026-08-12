<?php

declare(strict_types=1);

namespace App\Tests\Support\Loyalty;

use App\Loyalty\Application\Command\AddPoints;
use App\Loyalty\Application\Command\AddPointsHandler;
use App\Loyalty\Application\Command\CompleteTransfer;
use App\Loyalty\Application\Command\CompleteTransferHandler;
use App\Loyalty\Application\Command\CreateWallet;
use App\Loyalty\Application\Command\CreateWalletHandler;
use App\Loyalty\Application\Command\DepositPoints;
use App\Loyalty\Application\Command\DepositPointsHandler;
use App\Loyalty\Application\Command\FailTransfer;
use App\Loyalty\Application\Command\FailTransferHandler;
use App\Loyalty\Application\Command\InitiateTransfer;
use App\Loyalty\Application\Command\InitiateTransferHandler;
use App\Loyalty\Application\Command\RefundPoints;
use App\Loyalty\Application\Command\RefundPointsHandler;
use App\Loyalty\Application\Command\WithdrawPoints;
use App\Loyalty\Application\Command\WithdrawPointsHandler;
use App\Loyalty\Application\Event\TransferProcessManager;
use App\Loyalty\Domain\Transfer\Event\TransferInitiated;
use App\Loyalty\Domain\Transfer\Transfer;
use App\Loyalty\Domain\Transfer\TransferId;
use App\Loyalty\Domain\Wallet\Event\PointsAdded;
use App\Loyalty\Domain\Wallet\Event\PointsWithdrawn;
use App\Loyalty\Domain\Wallet\WalletId;
use App\Shared\Application\Command\Command;
use App\Shared\Application\Command\CommandBus;
use App\Shared\Domain\EventSourcing\DomainEvent;
use App\Tests\Support\Loyalty\Stub\InMemoryTransfers;
use App\Tests\Support\Loyalty\Stub\InMemoryWallets;
use App\Tests\Support\Shared\Stub\QueueingEventBus;
use Symfony\Component\Clock\MockClock;

/**
 * Runs the transfer saga in-process: commands are handled immediately and the
 * resulting events are fed back to the process manager until nothing is left.
 *
 * The point is to exercise the real handlers and the real process manager with
 * only the repositories and buses replaced, so the test asserts on the actual
 * choreography rather than on a mock-shaped imitation of it.
 */
final class TransferSagaHarness implements CommandBus
{
    /**
     * @var list<Command>
     */
    private array $pending = [];

    /**
     * @var list<Command>
     */
    private array $handled = [];

    private readonly QueueingEventBus $eventBus;
    private readonly InMemoryWallets $wallets;
    private readonly InMemoryTransfers $transfers;
    private readonly TransferProcessManager $processManager;
    private readonly MockClock $clock;

    public function __construct()
    {
        $this->eventBus = new QueueingEventBus();
        $this->wallets = new InMemoryWallets($this->eventBus);
        $this->transfers = new InMemoryTransfers($this->eventBus);
        $this->clock = new MockClock('2026-01-01 12:00:00');
        $this->processManager = new TransferProcessManager($this->transfers, $this);
    }

    public function dispatch(Command $command): void
    {
        $this->pending[] = $command;
    }

    /**
     * Alternates between handling queued commands and letting the process
     * manager react to the events they produced, until the saga goes quiet.
     */
    public function run(): void
    {
        $safetyLimit = 50;

        while ([] !== $this->pending || !$this->eventBus->isEmpty()) {
            if (--$safetyLimit < 0) {
                throw new \RuntimeException('The saga did not settle; it is looping.');
            }

            $command = array_shift($this->pending);

            if (null !== $command) {
                $this->handle($command);

                continue;
            }

            $event = $this->eventBus->drain();

            if (null !== $event) {
                $this->react($event);
            }
        }
    }

    public function createWallet(int $initialPoints = 0): WalletId
    {
        $walletId = WalletId::generate();

        $this->dispatch(new CreateWallet($walletId->value));

        if ($initialPoints > 0) {
            $this->dispatch(new AddPoints($walletId->value, $initialPoints));
        }

        $this->run();

        return $walletId;
    }

    public function initiateTransfer(WalletId $source, WalletId $target, int $points): TransferId
    {
        $transferId = TransferId::generate();

        $this->dispatch(new InitiateTransfer($transferId->value, $source->value, $target->value, $points));
        $this->run();

        return $transferId;
    }

    public function balanceOf(WalletId $walletId): int
    {
        return $this->wallets->balanceOf($walletId);
    }

    public function transfer(TransferId $transferId): Transfer
    {
        return $this->transfers->get($transferId);
    }

    public function wallets(): InMemoryWallets
    {
        return $this->wallets;
    }

    /**
     * @return list<Command>
     */
    public function handledCommands(): array
    {
        return $this->handled;
    }

    public function hasHandled(string $commandClass): bool
    {
        foreach ($this->handled as $command) {
            if ($command instanceof $commandClass) {
                return true;
            }
        }

        return false;
    }

    private function handle(Command $command): void
    {
        $this->handled[] = $command;

        match (true) {
            $command instanceof CreateWallet => (new CreateWalletHandler($this->wallets, $this->clock))($command),
            $command instanceof AddPoints => (new AddPointsHandler($this->wallets, $this->clock))($command),
            $command instanceof InitiateTransfer => (new InitiateTransferHandler($this->transfers, $this->clock))($command),
            $command instanceof WithdrawPoints => (new WithdrawPointsHandler($this->wallets, $this, $this->clock))($command),
            $command instanceof DepositPoints => (new DepositPointsHandler($this->wallets, $this, $this->clock))($command),
            $command instanceof RefundPoints => (new RefundPointsHandler($this->wallets, $this->clock))($command),
            $command instanceof CompleteTransfer => (new CompleteTransferHandler($this->transfers, $this->clock))($command),
            $command instanceof FailTransfer => (new FailTransferHandler($this->transfers, $this->clock))($command),
            default => throw new \LogicException(\sprintf('No handler wired for "%s".', $command::class)),
        };
    }

    private function react(DomainEvent $event): void
    {
        match (true) {
            $event instanceof TransferInitiated => $this->processManager->onTransferInitiated($event),
            $event instanceof PointsWithdrawn => $this->processManager->onPointsWithdrawn($event),
            $event instanceof PointsAdded => $this->processManager->onPointsAdded($event),
            default => null,
        };
    }
}
