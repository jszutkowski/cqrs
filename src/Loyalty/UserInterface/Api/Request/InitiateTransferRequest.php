<?php

declare(strict_types=1);

namespace App\Loyalty\UserInterface\Api\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class InitiateTransferRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Field "sourceWalletId" is required.')]
        #[Assert\Uuid(message: 'Field "sourceWalletId" must be a UUID.')]
        public ?string $sourceWalletId = null,
        #[Assert\NotBlank(message: 'Field "targetWalletId" is required.')]
        #[Assert\Uuid(message: 'Field "targetWalletId" must be a UUID.')]
        public ?string $targetWalletId = null,
        #[Assert\NotNull(message: 'Field "points" is required.')]
        #[Assert\Positive(message: 'Field "points" must be greater than zero.')]
        public ?int $points = null,
    ) {
    }
}
