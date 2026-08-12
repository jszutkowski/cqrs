<?php

declare(strict_types=1);

namespace App\Loyalty\Application\Command;

use App\Loyalty\Application\Event\TransferDebitRejected;
use App\Loyalty\Domain\Transfer\TransferId;
use App\Loyalty\Domain\Wallet\Exception\InsufficientPoints;
use App\Loyalty\Domain\Wallet\Exception\WalletNotFound;
use App\Loyalty\Domain\Wallet\Points;
use App\Loyalty\Domain\Wallet\WalletId;
use App\Loyalty\Domain\Wallet\Wallets;
use App\Shared\Application\Bus\MessageBus;
use App\Shared\Application\Event\ProcessEventBus;
use Psr\Clock\ClockInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Debit leg of the transfer process.
 *
 * A domain rejection here is a business outcome, not an error, so the handler
 * reports it instead of throwing — the message is consumed and the process
 * moves on. What that rejection *means* for the transfer is not decided here:
 * this handler performs a withdrawal and says how it went, and
 * TransferProcessManager owns every decision about the process itself.
 */
#[AsMessageHandler(bus: MessageBus::COMMAND)]
final readonly class WithdrawPointsHandler
{
    public function __construct(
        private Wallets $wallets,
        private ProcessEventBus $processEvents,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(WithdrawPoints $command): void
    {
        try {
            $wallet = $this->wallets->get(WalletId::fromString($command->walletId));
            $wallet->withdraw(
                Points::of($command->points),
                TransferId::fromString($command->transferId),
                $this->clock->now(),
            );
        } catch (WalletNotFound|InsufficientPoints $exception) {
            $this->processEvents->publish(
                new TransferDebitRejected($command->transferId, $exception->getMessage()),
            );

            return;
        }

        $this->wallets->save($wallet);
    }
}
