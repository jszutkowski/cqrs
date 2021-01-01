<?php
declare(strict_types=1);

namespace App\Application\Command;


use App\Domain\Loyalty\Wallet;
use App\Domain\Loyalty\Wallets;
use Symfony\Component\Uid\Uuid;

class CreateWalletHandler
{
    /**
     * @var Wallets
     */
    private Wallets $wallets;

    public function __construct(Wallets $wallets)
    {
        $this->wallets = $wallets;
    }

    public function __invoke(WithdrawPoints $command): void
    {
        $wallet = Wallet::create((string)Uuid::v4());

        $this->wallets->store($wallet);
    }
}
