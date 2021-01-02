<?php

declare(strict_types=1);

namespace App\Application\CommandHandler;

use App\Application\Command\AddPoints;
use App\Domain\EventSourcing\NoResultException;
use App\Domain\Exception\DomainException;
use App\Domain\Loyalty\Points;
use App\Domain\Loyalty\WalletsRepository;

class AddPointsHandler
{
    private WalletsRepository $walletsRepository;

    public function __construct(WalletsRepository $walletsRepository)
    {
        $this->walletsRepository = $walletsRepository;
    }

    /**
     * @throws DomainException
     * @throws NoResultException
     */
    public function __invoke(AddPoints $command): void
    {
        $wallet = $this->walletsRepository->find($command->getWalletId());

        $wallet->addPoints(new Points($command->getAmount()));

        $this->walletsRepository->save($wallet);
    }
}
