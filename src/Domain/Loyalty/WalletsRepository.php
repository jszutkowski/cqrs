<?php

declare(strict_types=1);

namespace App\Domain\Loyalty;

use App\Domain\EventSourcing\NoResultException;

interface WalletsRepository
{
    public function save(Wallet $wallet): void;

    /**
     * @throws NoResultException
     */
    public function find(string $id): Wallet;
}
