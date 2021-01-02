<?php

declare(strict_types=1);

namespace App\Domain\Loyalty;

use App\Domain\Event;
use App\Domain\EventSourcing\AbstractAggregate;
use App\Domain\Exception\DomainException;
use App\Domain\Loyalty\Events\PointsAdded;
use App\Domain\Loyalty\Events\WalletCreated;

class Wallet extends AbstractAggregate
{
    /**
     * @var Points[]
     */
    private array $points = [];

    /**
     * @throws DomainException
     */
    public static function create(string $id): self
    {
        $wallet = new self();

        $wallet->recordThat(new WalletCreated($id));

        return $wallet;
    }

    /**
     * @param Event[] $events
     *
     * @throws DomainException
     */
    public static function fromEvents(array $events): self
    {
        $wallet = new self();

        foreach ($events as $event) {
            $wallet->handle($event);
            $wallet->version = $event->getVersion();
        }

        return $wallet;
    }

    /**
     * @throws DomainException
     */
    public function addPoints(Points $points): void
    {
        $this->recordThat(new PointsAdded($this->aggregateId, $points));
    }

    /**
     * @throws DomainException
     */
    protected function handle(Event $event): void
    {
        switch (get_class($event)) {
            case WalletCreated::class:
                /* @var WalletCreated $event */
                $this->aggregateId = $event->getAggregateId();
                break;
            case PointsAdded::class:
                /* @var PointsAdded $event */
                $this->points[] = $event->getPoints();
                break;
            default:
                throw new DomainException(sprintf('Unsupported event to handle: %s', get_class($event)));
        }
    }
}
