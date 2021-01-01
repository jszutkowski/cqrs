<?php
declare(strict_types=1);

namespace App\Domain\Loyalty\Events;


use App\Domain\Event;
use App\Domain\Loyalty\Points;

class PointsAdded extends Event
{
    private string $id;
    private Points $points;

    public function __construct(string $id, Points $points)
    {
        parent::__construct();

        $this->id = $id;
        $this->points = $points;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getPoints(): Points
    {
        return $this->points;
    }
}
