<?php

declare(strict_types=1);

namespace App\Loyalty\Application\Command;

use App\Loyalty\Application\Exception\WalletDoesNotExist;
use App\Loyalty\Domain\Wallet\Exception\WalletNotFound;
use App\Loyalty\Domain\Wallet\Points;
use App\Loyalty\Domain\Wallet\WalletId;
use App\Loyalty\Domain\Wallet\Wallets;
use Psr\Clock\ClockInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class AddPointsHandler
{
    public function __construct(
        private Wallets $wallets,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(AddPoints $command): void
    {
        try {
            $wallet = $this->wallets->get(WalletId::fromString($command->walletId));
        } catch (WalletNotFound) {
            throw WalletDoesNotExist::withId($command->walletId);
        }

        $wallet->addPoints(Points::of($command->points), $this->clock->now());

        $this->wallets->save($wallet);
    }
}
