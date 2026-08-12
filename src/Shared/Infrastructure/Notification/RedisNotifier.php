<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Notification;

use App\Shared\Application\Notification\Notification;
use App\Shared\Application\Notification\Notifier;
use Predis\ClientInterface;
use Psr\Log\LoggerInterface;

final readonly class RedisNotifier implements Notifier
{
    public function __construct(
        private ClientInterface $redis,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Best effort by contract (see the Notifier port): a live update is a
     * convenience on top of the read model, never the source of truth, so a
     * broken Redis must not fail the projection that already committed.
     */
    public function notify(Notification $notification): void
    {
        $message = json_encode([
            'name' => $notification->name,
            'payload' => $notification->payload,
        ], \JSON_THROW_ON_ERROR);

        try {
            $this->redis->publish($notification->channel, $message);
        } catch (\Throwable $exception) {
            $this->logger->error('Failed to publish a live update', [
                'channel' => $notification->channel,
                'name' => $notification->name,
                'exception' => $exception,
            ]);
        }
    }
}
