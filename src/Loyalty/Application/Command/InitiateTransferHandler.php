<?php

declare(strict_types=1);

namespace App\Loyalty\Application\Command;

use App\Loyalty\Application\Exception\TransferRejected;
use App\Loyalty\Domain\Transfer\Exception\InvalidTransfer;
use App\Loyalty\Domain\Transfer\Transfer;
use App\Loyalty\Domain\Transfer\TransferId;
use App\Loyalty\Domain\Transfer\Transfers;
use App\Loyalty\Domain\Wallet\Points;
use App\Loyalty\Domain\Wallet\WalletId;
use App\Shared\Application\Bus\MessageBus;
use Psr\Clock\ClockInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: MessageBus::COMMAND)]
final readonly class InitiateTransferHandler
{
    public function __construct(
        private Transfers $transfers,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(InitiateTransfer $command): void
    {
        try {
            $transfer = Transfer::initiate(
                TransferId::fromString($command->transferId),
                WalletId::fromString($command->sourceWalletId),
                WalletId::fromString($command->targetWalletId),
                Points::of($command->points),
                $this->clock->now(),
            );
        } catch (InvalidTransfer $exception) {
            throw TransferRejected::because($exception->getMessage());
        }

        $this->transfers->save($transfer);
    }
}
