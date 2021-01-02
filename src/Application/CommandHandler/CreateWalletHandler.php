<?php

declare(strict_types=1);

namespace App\Application\CommandHandler;

use App\Application\Command\CreateWallet;
use App\Domain\Loyalty\Wallet;
use App\Domain\Loyalty\WalletsRepository;
use Symfony\Component\Uid\Uuid;

class CreateWalletHandler
{
    private WalletsRepository $walletsRepository;

    public function __construct(WalletsRepository $walletsRepository)
    {
        $this->walletsRepository = $walletsRepository;
    }

    public function __invoke(CreateWallet $command): void
    {
        $wallet = Wallet::create((string) Uuid::v4());

        $this->walletsRepository->save($wallet);
    }
}
