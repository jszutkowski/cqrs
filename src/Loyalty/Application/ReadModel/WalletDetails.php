<?php

declare(strict_types=1);

namespace App\Loyalty\Application\ReadModel;

final readonly class WalletDetails implements \JsonSerializable
{
    /**
     * @param list<PointsEntry> $history
     */
    public function __construct(
        public string $walletId,
        public int $balance,
        public array $history,
    ) {
    }

    /**
     * @return array{walletId: string, balance: int, history: list<PointsEntry>}
     */
    public function jsonSerialize(): array
    {
        return [
            'walletId' => $this->walletId,
            'balance' => $this->balance,
            'history' => $this->history,
        ];
    }
}
