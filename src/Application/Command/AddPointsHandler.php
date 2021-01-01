<?php
declare(strict_types=1);

namespace App\Application\Command;


use App\Domain\Loyalty\Points;
use App\Domain\Loyalty\Wallets;

class AddPointsHandler
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
            /* @todo: - null will be no longer returned - exception will be thrown */

            throw new \Exception('No wallet found!');
        }

        $wallet->addPoints(new Points($command->getPoints()));

        $this->wallets->store($wallet);
    }
}
