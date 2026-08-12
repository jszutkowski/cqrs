<?php

declare(strict_types=1);

namespace App\Tests\Functional\Loyalty;

use App\Loyalty\Domain\Transfer\TransferStatus;
use App\Loyalty\UserInterface\Api\TransferController;
use App\Tests\Functional\ApiTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

#[CoversClass(TransferController::class)]
final class TransferApiTest extends ApiTestCase
{
    #[Test]
    public function it_moves_points_between_two_wallets(): void
    {
        $source = $this->createWallet(500);
        $target = $this->createWallet(100);

        $transferId = $this->initiateTransfer($source, $target, 200);

        self::assertSame(300, $this->balanceOf($source));
        self::assertSame(300, $this->balanceOf($target));
        self::assertSame(TransferStatus::Completed->value, $this->transferStatus($transferId));
    }

    #[Test]
    public function it_records_the_transfer_on_both_sides_of_the_history(): void
    {
        $source = $this->createWallet(500);
        $target = $this->createWallet(0);

        $transferId = $this->initiateTransfer($source, $target, 150);

        $sourceHistory = $this->decodeList($this->request('GET', '/api/wallets/'.$source), 'history');
        $targetHistory = $this->decodeList($this->request('GET', '/api/wallets/'.$target), 'history');

        $sourceEntry = $this->entry($sourceHistory, 0);
        $targetEntry = $this->entry($targetHistory, 0);

        self::assertSame(-150, $sourceEntry['amount']);
        self::assertSame($transferId, $sourceEntry['transferId']);
        self::assertSame(150, $targetEntry['amount']);
        self::assertSame($transferId, $targetEntry['transferId']);
    }

    #[Test]
    public function it_fails_the_transfer_and_leaves_balances_untouched_when_the_source_is_short(): void
    {
        $source = $this->createWallet(50);
        $target = $this->createWallet(10);

        $transferId = $this->initiateTransfer($source, $target, 500);

        self::assertSame(50, $this->balanceOf($source));
        self::assertSame(10, $this->balanceOf($target));
        self::assertSame(TransferStatus::Failed->value, $this->transferStatus($transferId));
    }

    #[Test]
    public function it_explains_why_a_transfer_failed(): void
    {
        $source = $this->createWallet(50);
        $target = $this->createWallet(0);

        $transferId = $this->initiateTransfer($source, $target, 500);

        $transfer = $this->decode($this->request('GET', '/api/transfers/'.$transferId));

        self::assertArrayHasKey('failureReason', $transfer);
        self::assertNotNull($transfer['failureReason']);
    }

    #[Test]
    public function it_refunds_the_source_when_the_target_wallet_does_not_exist(): void
    {
        $source = $this->createWallet(500);
        $missingTarget = Uuid::v4()->toRfc4122();

        $transferId = $this->initiateTransfer($source, $missingTarget, 200);

        self::assertSame(500, $this->balanceOf($source), 'The compensating refund must restore the balance.');
        self::assertSame(TransferStatus::Failed->value, $this->transferStatus($transferId));
    }

    #[Test]
    public function it_answers_404_for_an_unknown_transfer(): void
    {
        $response = $this->request('GET', '/api/transfers/'.Uuid::v4()->toRfc4122());

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @param array<string, mixed> $payload
     */
    #[Test]
    #[DataProvider('invalidTransferPayloads')]
    public function it_rejects_an_invalid_transfer_payload(array $payload): void
    {
        $response = $this->request('POST', '/api/transfers', $payload);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    /**
     * @return iterable<string, array{payload: array<string, mixed>}>
     */
    public static function invalidTransferPayloads(): iterable
    {
        $walletId = Uuid::v4()->toRfc4122();

        yield 'empty body' => ['payload' => []];
        yield 'missing target' => ['payload' => ['sourceWalletId' => $walletId, 'points' => 10]];
        yield 'zero points' => ['payload' => [
            'sourceWalletId' => $walletId,
            'targetWalletId' => Uuid::v4()->toRfc4122(),
            'points' => 0,
        ]];
        yield 'source is not a uuid' => ['payload' => [
            'sourceWalletId' => 'not-a-uuid',
            'targetWalletId' => Uuid::v4()->toRfc4122(),
            'points' => 10,
        ]];
    }

    private function createWallet(int $initialPoints): string
    {
        $walletId = $this->decodeString($this->request('POST', '/api/wallets'), 'walletId');

        if ($initialPoints > 0) {
            $this->request('POST', '/api/wallets/'.$walletId.'/points', ['points' => $initialPoints]);
        }

        return $walletId;
    }

    private function initiateTransfer(string $source, string $target, int $points): string
    {
        $response = $this->request('POST', '/api/transfers', [
            'sourceWalletId' => $source,
            'targetWalletId' => $target,
            'points' => $points,
        ]);

        self::assertSame(Response::HTTP_ACCEPTED, $response->getStatusCode());

        return $this->decodeString($response, 'transferId');
    }

    private function balanceOf(string $walletId): int
    {
        return $this->decodeInt($this->request('GET', '/api/wallets/'.$walletId), 'balance');
    }

    private function transferStatus(string $transferId): string
    {
        return $this->decodeString($this->request('GET', '/api/transfers/'.$transferId), 'status');
    }
}
