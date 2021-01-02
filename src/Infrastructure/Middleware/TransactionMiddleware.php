<?php

declare(strict_types=1);

namespace App\Infrastructure\Middleware;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ConnectionException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

class TransactionMiddleware implements MiddlewareInterface
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @throws ConnectionException
     * @throws \Throwable
     */
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        try {
            $this->connection->beginTransaction();

            $envelope = $stack->next()->handle($envelope, $stack);

            $this->connection->commit();

            return $envelope;
        } catch (\Throwable $exception) {
            $this->connection->rollBack();

            if ($exception instanceof HandlerFailedException) {
                // Remove all HandledStamp from the envelope so the retry will execute all handlers again.
                // When a handler fails, the queries of allegedly successful previous handlers just got rolled back.
                throw new HandlerFailedException($exception->getEnvelope()->withoutAll(HandledStamp::class), $exception->getNestedExceptions());
            }

            throw $exception;
        }
    }
}
