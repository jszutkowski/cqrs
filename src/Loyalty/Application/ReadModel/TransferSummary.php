<?php

declare(strict_types=1);

namespace App\Loyalty\Application\ReadModel;

use App\Loyalty\Domain\Transfer\TransferStatus;

final readonly class TransferSummary implements \JsonSerializable
{
    public function __construct(
        public string $transferId,
        public string $sourceWalletId,
        public string $targetWalletId,
        public int $points,
        public TransferStatus $status,
        public ?string $failureReason,
    ) {
    }

    /**
     * @return array{transferId: string, sourceWalletId: string, targetWalletId: string, points: int, status: string, failureReason: string|null}
     */
    public function jsonSerialize(): array
    {
        return [
            'transferId' => $this->transferId,
            'sourceWalletId' => $this->sourceWalletId,
            'targetWalletId' => $this->targetWalletId,
            'points' => $this->points,
            'status' => $this->status->value,
            'failureReason' => $this->failureReason,
        ];
    }
}
