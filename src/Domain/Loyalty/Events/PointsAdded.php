<?php

declare(strict_types=1);

namespace App\Domain\Loyalty\Events;

use App\Domain\Event;
use App\Domain\Loyalty\Points;

class PointsAdded extends Event
{
    private Points $points;

    public function __construct(string $walletId, Points $points)
    {
        parent::__construct($walletId);

        $this->points = $points;
    }

    public function getPoints(): Points
    {
        return $this->points;
    }
}
