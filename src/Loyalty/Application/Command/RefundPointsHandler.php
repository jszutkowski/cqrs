<?php

declare(strict_types=1);

namespace App\Loyalty\Application\Command;

use App\Loyalty\Application\Exception\WalletDoesNotExist;
use App\Loyalty\Domain\Transfer\TransferId;
use App\Loyalty\Domain\Wallet\Exception\WalletNotFound;
use App\Loyalty\Domain\Wallet\Points;
use App\Loyalty\Domain\Wallet\WalletId;
use App\Loyalty\Domain\Wallet\Wallets;
use Psr\Clock\ClockInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class RefundPointsHandler
{
    public function __construct(
        private Wallets $wallets,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(RefundPoints $command): void
    {
        try {
            $wallet = $this->wallets->get(WalletId::fromString($command->walletId));
        } catch (WalletNotFound) {
            // The source wallet was debited moments ago, so it must exist;
            // losing it mid-compensation is unrecoverable here and belongs in
            // the failure transport for a human to look at.
            throw WalletDoesNotExist::withId($command->walletId);
        }

        $wallet->refund(
            Points::of($command->points),
            TransferId::fromString($command->transferId),
            $this->clock->now(),
        );

        $this->wallets->save($wallet);
    }
}
