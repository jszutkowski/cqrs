<?php

declare(strict_types=1);

namespace App\Loyalty\Application\ReadModel;

final readonly class WalletSummary implements \JsonSerializable
{
    public function __construct(
        public string $walletId,
        public int $balance,
    ) {
    }

    /**
     * @return array{walletId: string, balance: int}
     */
    public function jsonSerialize(): array
    {
        return [
            'walletId' => $this->walletId,
            'balance' => $this->balance,
        ];
    }
}
