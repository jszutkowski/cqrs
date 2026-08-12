<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

/**
 * Wraps each handled message in a database transaction.
 *
 * Symfony ships doctrine_transaction for this, but that middleware needs an
 * ORM entity manager and this application deliberately runs on DBAL alone.
 *
 * Nested dispatches (a handler dispatching another command synchronously) join
 * the outer transaction rather than opening a second one, which is what makes
 * the whole command safe to retry as a unit.
 */
final readonly class TransactionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $this->connection->beginTransaction();

        try {
            $envelope = $stack->next()->handle($envelope, $stack);

            $this->connection->commit();

            return $envelope;
        } catch (\Throwable $exception) {
            $this->connection->rollBack();

            throw $exception;
        }
    }
}
