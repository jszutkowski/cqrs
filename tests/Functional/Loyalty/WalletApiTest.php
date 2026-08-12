<?php

declare(strict_types=1);

namespace App\Tests\Functional\Loyalty;

use App\Loyalty\UserInterface\Api\WalletController;
use App\Tests\Functional\ApiTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

#[CoversClass(WalletController::class)]
final class WalletApiTest extends ApiTestCase
{
    #[Test]
    public function it_rejects_an_unauthenticated_request(): void
    {
        $this->client->request('GET', '/api/wallets');

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function it_accepts_wallet_creation_and_returns_the_identifier(): void
    {
        $response = $this->request('POST', '/api/wallets');

        self::assertSame(Response::HTTP_ACCEPTED, $response->getStatusCode());
        self::assertTrue(
            Uuid::isValid($this->decodeString($response, 'walletId')),
            'The caller must learn which wallet it created.',
        );
    }

    #[Test]
    public function it_returns_the_created_wallet_with_a_zero_balance(): void
    {
        $walletId = $this->createWallet();

        $response = $this->request('GET', '/api/wallets/'.$walletId);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(
            ['walletId' => $walletId, 'balance' => 0, 'history' => []],
            $this->decode($response),
        );
    }

    #[Test]
    public function it_reflects_added_points_in_the_read_model(): void
    {
        $walletId = $this->createWallet();

        $accepted = $this->request('POST', '/api/wallets/'.$walletId.'/points', ['points' => 120]);
        self::assertSame(Response::HTTP_ACCEPTED, $accepted->getStatusCode());

        $wallet = $this->request('GET', '/api/wallets/'.$walletId);
        $history = $this->decodeList($wallet, 'history');

        self::assertSame(120, $this->decodeInt($wallet, 'balance'));
        self::assertCount(1, $history);
        self::assertSame(120, $this->entry($history, 0)['amount']);
    }

    #[Test]
    public function it_lists_every_wallet(): void
    {
        $this->createWallet();
        $this->createWallet();

        $wallets = $this->decode($this->request('GET', '/api/wallets'));

        self::assertCount(2, $wallets);
        self::assertArrayHasKey('balance', $this->entry($wallets, 0));
    }

    #[Test]
    public function it_answers_404_for_an_unknown_wallet(): void
    {
        $response = $this->request('GET', '/api/wallets/'.Uuid::v4()->toRfc4122());

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        self::assertArrayHasKey('message', $this->decode($response));
    }

    /**
     * @param array<string, mixed> $payload
     */
    #[Test]
    #[DataProvider('invalidPointsPayloads')]
    public function it_rejects_an_invalid_points_payload(array $payload): void
    {
        $walletId = $this->createWallet();

        $response = $this->request('POST', '/api/wallets/'.$walletId.'/points', $payload);

        self::assertSame(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $response->getStatusCode(),
            'An invalid payload must be a client error, not a 500.',
        );
    }

    /**
     * @return iterable<string, array{payload: array<string, mixed>}>
     */
    public static function invalidPointsPayloads(): iterable
    {
        yield 'missing points field' => ['payload' => []];
        yield 'zero points' => ['payload' => ['points' => 0]];
        yield 'negative points' => ['payload' => ['points' => -50]];
    }

    private function createWallet(): string
    {
        return $this->decodeString($this->request('POST', '/api/wallets'), 'walletId');
    }
}
