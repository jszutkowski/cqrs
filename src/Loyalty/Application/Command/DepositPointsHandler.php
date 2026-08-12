<?php

declare(strict_types=1);

namespace App\Loyalty\Application\Command;

use App\Loyalty\Application\Event\TransferCreditRejected;
use App\Loyalty\Domain\Transfer\TransferId;
use App\Loyalty\Domain\Wallet\Exception\WalletNotFound;
use App\Loyalty\Domain\Wallet\Points;
use App\Loyalty\Domain\Wallet\WalletId;
use App\Loyalty\Domain\Wallet\Wallets;
use App\Shared\Application\Bus\MessageBus;
use App\Shared\Application\Event\ProcessEventBus;
use Psr\Clock\ClockInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Credit leg of the transfer process.
 *
 * If the target wallet turns out to be gone after the source was already
 * debited, the handler does not compensate on its own — it reports the
 * rejection together with what a refund would need. Deciding to refund is a
 * decision about the process, and those all live in TransferProcessManager.
 */
#[AsMessageHandler(bus: MessageBus::COMMAND)]
final readonly class DepositPointsHandler
{
    public function __construct(
        private Wallets $wallets,
        private ProcessEventBus $processEvents,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(DepositPoints $command): void
    {
        try {
            $wallet = $this->wallets->get(WalletId::fromString($command->walletId));
        } catch (WalletNotFound $exception) {
            $this->processEvents->publish(new TransferCreditRejected(
                $command->transferId,
                $command->sourceWalletId,
                $command->points,
                $exception->getMessage(),
            ));

            return;
        }

        $wallet->deposit(
            Points::of($command->points),
            TransferId::fromString($command->transferId),
            $this->clock->now(),
        );

        $this->wallets->save($wallet);
    }
}
