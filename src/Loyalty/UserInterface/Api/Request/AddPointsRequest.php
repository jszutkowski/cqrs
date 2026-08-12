<?php

declare(strict_types=1);

namespace App\Loyalty\UserInterface\Api\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class AddPointsRequest
{
    public function __construct(
        #[Assert\NotNull(message: 'Field "points" is required.')]
        #[Assert\Positive(message: 'Field "points" must be greater than zero.')]
        public ?int $points = null,
    ) {
    }
}
