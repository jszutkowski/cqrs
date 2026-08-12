<?php

declare(strict_types=1);

namespace App\Loyalty\Application\ReadModel;

final readonly class PointsEntry implements \JsonSerializable
{
    public function __construct(
        public int $amount,
        public \DateTimeImmutable $registeredAt,
        public ?string $transferId,
    ) {
    }

    /**
     * @return array{amount: int, registeredAt: string, transferId: string|null}
     */
    public function jsonSerialize(): array
    {
        return [
            'amount' => $this->amount,
            'registeredAt' => $this->registeredAt->format(\DateTimeInterface::ATOM),
            'transferId' => $this->transferId,
        ];
    }
}
