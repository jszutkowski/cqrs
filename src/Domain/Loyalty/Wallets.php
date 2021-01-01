<?php
declare(strict_types=1);

namespace App\Domain\Loyalty;


use App\Domain\EventSourcing\NoResultException;

interface Wallets
{
    /**
     * @param Wallet $wallet
     */
    public function store(Wallet $wallet): void;

    /**
     * @param string $uuid
     * @return Wallet
     * @throws NoResultException
     */
    public function get(string $uuid): Wallet;
}
