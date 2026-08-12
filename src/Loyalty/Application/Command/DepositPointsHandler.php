<?php

declare(strict_types=1);

namespace App\Loyalty\Application\Command;

use App\Loyalty\Domain\Transfer\TransferId;
use App\Loyalty\Domain\Wallet\Exception\WalletNotFound;
use App\Loyalty\Domain\Wallet\Points;
use App\Loyalty\Domain\Wallet\WalletId;
use App\Loyalty\Domain\Wallet\Wallets;
use App\Shared\Application\Command\CommandBus;
use Psr\Clock\ClockInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Credit leg of the transfer process. If the target wallet vanished after the
 * source was already debited, the handler compensates: refund the source and
 * settle the transfer as failed.
 */
#[AsMessageHandler(bus: 'command.bus')]
final readonly class DepositPointsHandler
{
    public function __construct(
        private Wallets $wallets,
        private CommandBus $commandBus,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(DepositPoints $command): void
    {
        try {
            $wallet = $this->wallets->get(WalletId::fromString($command->walletId));
        } catch (WalletNotFound $exception) {
            $this->commandBus->dispatch(new RefundPoints($command->sourceWalletId, $command->points, $command->transferId));
            $this->commandBus->dispatch(new FailTransfer($command->transferId, $exception->getMessage()));

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
