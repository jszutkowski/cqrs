<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class IntegrationTestCase extends KernelTestCase
{
    protected Connection $connection;

    protected function setUp(): void
    {
        self::bootKernel();

        $connection = self::getContainer()->get(Connection::class);
        \assert($connection instanceof Connection);
        $this->connection = $connection;

        $this->resetSchema();
    }

    /**
     * Every test starts from an empty database: projections and event streams
     * are append-only, so leftovers from a previous test would quietly change
     * the outcome of the next one.
     */
    private function resetSchema(): void
    {
        foreach (['events', 'wallet_points', 'wallets', 'transfers'] as $table) {
            $this->connection->executeStatement(\sprintf('DELETE FROM %s', $table));
        }
    }
}
