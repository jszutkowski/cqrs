<?php
declare(strict_types=1);

namespace App\Application\Command;


use App\Domain\Loyalty\Points;
use App\Domain\Loyalty\Wallets;

class WithdrawPointsHandler
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
        $wallet = $this->wallets->get($command->getWalletId());

        if (null === $wallet) {
            /* @todo: - how to handle this exception when it will be handled async? */

            throw new \Exception('No wallet found!');
        }

        $wallet->withdrawPoints(new Points($command->getPoints()));

        $this->wallets->store($wallet);
    }
}
