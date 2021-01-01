<?php
declare(strict_types=1);

namespace App\Domain\Loyalty;


use App\Domain\Event;
use App\Domain\EventSourcing\AbstractAggregateRoot;
use App\Domain\Exception\DomainException;
use App\Domain\Loyalty\Events\PointsAdded;
use App\Domain\Loyalty\Events\PointsWithdrawn;
use App\Domain\Loyalty\Events\WalletCreated;

class Wallet extends AbstractAggregateRoot
{
    /**
     * @var Points[]
     */
    private array $points = [];

    /**
     * @param string $id
     * @return static
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
     * @return static
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

    public function addPoints(Points $points): void
    {
        $this->recordThat(new PointsAdded($this->aggregateRootId, $points));
    }

    public function withdrawPoints(Points $points): void
    {
        $this->recordThat(new PointsWithdrawn($this->aggregateRootId, $points));
    }

    /**
     * @return Points
     */
    public function getBalance(): Points
    {
        $points = array_reduce($this->points, static function(int $sum, Points $points) {
            return $sum + $points->getAmount();
        }, 0);

        return new Points($points);
    }

    /**
     * @param Event $event
     * @throws DomainException
     */
    protected function handle(Event $event): void
    {
        switch (get_class($event)) {
            case WalletCreated::class:
                /** @var $event WalletCreated */
                $this->aggregateRootId = $event->getId();
                break;
            case PointsAdded::class:
                /** @var $event PointsAdded */
                $this->points[] = $event->getPoints();
                break;
            case PointsWithdrawn::class:
                /** @var $event PointsWithdrawn */
                $this->points[] = new Points($this->getBalance()->getAmount() - $event->getPoints()->getAmount());
                break;
            default:
                throw new DomainException(sprintf('Unsupported event to handle: %s', get_class($event)));
        }
    }
}
