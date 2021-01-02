<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifier;

use App\Application\Notifier\Event;
use App\Application\Notifier\NotifierInterface;
use Predis\Client;
use Psr\Log\LoggerInterface;

class RedisNotifier implements NotifierInterface
{
    private Client $client;
    private LoggerInterface $logger;

    public function __construct(Client $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    public function notify(Event $event): void
    {
        $data = [
            'eventName' => $event->eventName,
            'payload' => $event->payload,
        ];

        try {
            $this->client->publish($event->channel, json_encode($data));
        } catch (\Throwable $e) {
            $this->logger->error('Error occurred on publishing event', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
