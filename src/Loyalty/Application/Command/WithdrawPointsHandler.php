<?php

declare(strict_types=1);

namespace App\Loyalty\Application\Command;

use App\Loyalty\Domain\Transfer\TransferId;
use App\Loyalty\Domain\Wallet\Exception\InsufficientPoints;
use App\Loyalty\Domain\Wallet\Exception\WalletNotFound;
use App\Loyalty\Domain\Wallet\Points;
use App\Loyalty\Domain\Wallet\WalletId;
use App\Loyalty\Domain\Wallet\Wallets;
use App\Shared\Application\Command\CommandBus;
use Psr\Clock\ClockInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Debit leg of the transfer process. A domain rejection here is a business
 * outcome, not an error — the handler settles the transfer as failed instead
 * of throwing, so the message is consumed and the saga terminates cleanly.
 */
#[AsMessageHandler(bus: 'command.bus')]
final readonly class WithdrawPointsHandler
{
    public function __construct(
        private Wallets $wallets,
        private CommandBus $commandBus,
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
            $this->commandBus->dispatch(new FailTransfer($command->transferId, $exception->getMessage()));

            return;
        }

        $this->wallets->save($wallet);
    }
}
