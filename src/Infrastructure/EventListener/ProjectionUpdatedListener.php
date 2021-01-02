<?php

declare(strict_types=1);

namespace App\Infrastructure\EventListener;

use App\Application\Notifier\Channel;
use App\Application\Notifier\Event;
use App\Application\Notifier\Events;
use App\Application\Notifier\NotifierInterface;
use App\Domain\EventSourcing\DomainEventApplierTrait;
use App\Domain\Loyalty\Events\PointsAdded;
use App\Domain\Loyalty\Events\WalletCreated;
use Psr\Log\LoggerInterface;

class ProjectionUpdatedListener
{
    use DomainEventApplierTrait;

    private NotifierInterface $notifier;
    private LoggerInterface $logger;

    public function __construct(NotifierInterface $notifier, LoggerInterface $logger)
    {
        $this->notifier = $notifier;
        $this->logger = $logger;
    }

    public function onEventsApplied(ProjectionUpdated $event): void
    {
        try {
            $this->apply($event->getDomainEvents());
        } catch (\Throwable $e) {
            $this->logger->error('An error occurred when trying to handle events applied in projection', [
                'exception' => $e,
            ]);
        }
    }

    private function applyWalletCreated(WalletCreated $event): void
    {
        $this->notifier->notify(new Event(
            Channel::WALLETS,
            Events::WALLET_CREATED,
            [
                'walletId' => $event->getAggregateId(),
                'balance' => 0,
            ]
        ));
    }

    private function applyPointsAdded(PointsAdded $event): void
    {
        $this->notifier->notify(new Event(
            sprintf('%s:%s', Channel::WALLET, $event->getAggregateId()),
            Events::POINTS_ADDED,
            [
                'walletId' => $event->getAggregateId(),
                'amount' => $event->getPoints()->getAmount(),
                'createdAt' => $event->getCreatedAt()->format('Y-m-d H:i:s'),
            ]
        ));

        $this->notifier->notify(new Event(
            Channel::WALLETS,
            Events::POINTS_ADDED,
            [
                'walletId' => $event->getAggregateId(),
                'amount' => $event->getPoints()->getAmount(),
                'createdAt' => $event->getCreatedAt()->format('Y-m-d H:i:s'),
            ]
        ));
    }
}
