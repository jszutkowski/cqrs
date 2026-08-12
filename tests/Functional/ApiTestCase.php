<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

abstract class ApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected Connection $connection;

    private ?string $token = null;

    protected function setUp(): void
    {
        // Exceptions stay caught so the tests observe the real HTTP contract
        // (422 from the validator, 404 from the exception listener) rather than
        // the exception that produced it.
        $this->client = self::createClient();

        $connection = self::getContainer()->get(Connection::class);
        \assert($connection instanceof Connection);
        $this->connection = $connection;

        foreach (['events', 'wallet_points', 'wallets', 'transfers'] as $table) {
            $this->connection->executeStatement(\sprintf('DELETE FROM %s', $table));
        }
    }

    /**
     * @param array<string, mixed>|null $payload
     */
    protected function request(string $method, string $uri, ?array $payload = null): Response
    {
        $this->client->request(
            $method,
            $uri,
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer '.$this->token(),
            ],
            content: null !== $payload ? json_encode($payload, \JSON_THROW_ON_ERROR) : null,
        );

        return $this->client->getResponse();
    }

    /**
     * @return array<array-key, mixed>
     */
    protected function decode(Response $response): array
    {
        $content = $response->getContent();
        self::assertIsString($content);

        $decoded = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        return $decoded;
    }

    /**
     * @return array<array-key, mixed>
     */
    protected function decodeList(Response $response, string $key): array
    {
        $value = $this->decode($response)[$key] ?? null;
        self::assertIsArray($value);

        return $value;
    }

    /**
     * @param array<array-key, mixed> $list
     *
     * @return array<array-key, mixed>
     */
    protected function entry(array $list, int $index): array
    {
        self::assertArrayHasKey($index, $list);
        $entry = $list[$index];
        self::assertIsArray($entry);

        return $entry;
    }

    protected function decodeInt(Response $response, string $key): int
    {
        $value = $this->decode($response)[$key] ?? null;
        self::assertIsInt($value);

        return $value;
    }

    protected function decodeString(Response $response, string $key): string
    {
        $value = $this->decode($response)[$key] ?? null;
        self::assertIsString($value);

        return $value;
    }

    protected function token(): string
    {
        if (null !== $this->token) {
            return $this->token;
        }

        $this->client->request(
            'POST',
            '/api/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['username' => 'admin', 'password' => 'admin1'], \JSON_THROW_ON_ERROR),
        );

        $content = $this->client->getResponse()->getContent();
        \assert(\is_string($content));

        $decoded = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        \assert(\is_array($decoded) && isset($decoded['token']) && \is_string($decoded['token']));

        return $this->token = $decoded['token'];
    }
}
