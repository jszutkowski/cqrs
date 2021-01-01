<?php
declare(strict_types=1);

namespace App\Domain\Loyalty\Events;


use App\Domain\Event;

class WalletCreated extends Event
{
    private string $id;

    public function __construct(string $id)
    {
        parent::__construct();

        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }
}
