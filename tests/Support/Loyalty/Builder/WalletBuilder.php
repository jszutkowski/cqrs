<?php

declare(strict_types=1);

namespace App\Tests\Support\Loyalty\Builder;

use App\Loyalty\Domain\Wallet\Points;
use App\Loyalty\Domain\Wallet\Wallet;
use App\Loyalty\Domain\Wallet\WalletId;

final readonly class WalletBuilder
{
    private function __construct(
        private WalletId $walletId,
        private int $initialBalance,
        private \DateTimeImmutable $occurredAt,
    ) {
    }

    public static function create(): self
    {
        return new self(WalletId::generate(), 0, new \DateTimeImmutable('2026-01-01 12:00:00'));
    }

    public function withId(WalletId $walletId): self
    {
        return new self($walletId, $this->initialBalance, $this->occurredAt);
    }

    public function withBalance(int $balance): self
    {
        return new self($this->walletId, $balance, $this->occurredAt);
    }

    public function build(): Wallet
    {
        $wallet = Wallet::create($this->walletId, $this->occurredAt);

        if ($this->initialBalance > 0) {
            $wallet->addPoints(Points::of($this->initialBalance), $this->occurredAt);
        }

        return $wallet;
    }
}
