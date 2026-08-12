<?php

declare(strict_types=1);

namespace App\Loyalty\Application\Command;

use App\Loyalty\Domain\Wallet\Wallet;
use App\Loyalty\Domain\Wallet\WalletId;
use App\Loyalty\Domain\Wallet\Wallets;
use App\Shared\Application\Bus\MessageBus;
use Psr\Clock\ClockInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: MessageBus::COMMAND)]
final readonly class CreateWalletHandler
{
    public function __construct(
        private Wallets $wallets,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(CreateWallet $command): void
    {
        $wallet = Wallet::create(WalletId::fromString($command->walletId), $this->clock->now());

        $this->wallets->save($wallet);
    }
}
