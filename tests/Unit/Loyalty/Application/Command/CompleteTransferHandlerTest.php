<?php

declare(strict_types=1);

namespace App\Tests\Unit\Loyalty\Application\Command;

use App\Loyalty\Application\Command\CompleteTransfer;
use App\Loyalty\Application\Command\CompleteTransferHandler;
use App\Loyalty\Application\Exception\TransferDoesNotExist;
use App\Loyalty\Domain\Transfer\Transfer;
use App\Loyalty\Domain\Transfer\TransferId;
use App\Loyalty\Domain\Transfer\TransferStatus;
use App\Loyalty\Domain\Wallet\Points;
use App\Loyalty\Domain\Wallet\WalletId;
use App\Tests\Support\Loyalty\Stub\InMemoryTransfers;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

#[CoversClass(CompleteTransferHandler::class)]
final class CompleteTransferHandlerTest extends TestCase
{
    private InMemoryTransfers $transfers;
    private CompleteTransferHandler $handler;

    protected function setUp(): void
    {
        $this->transfers = new InMemoryTransfers();
        $this->handler = new CompleteTransferHandler($this->transfers, new MockClock('2026-01-01 12:00:00'));
    }

    #[Test]
    public function it_marks_the_transfer_as_completed(): void
    {
        $transferId = $this->givenInitiatedTransfer();

        ($this->handler)(new CompleteTransfer($transferId->value));

        self::assertSame(TransferStatus::Completed, $this->transfers->get($transferId)->status());
    }

    #[Test]
    public function it_ignores_a_redelivered_command_for_a_settled_transfer(): void
    {
        $transferId = $this->givenInitiatedTransfer();
        ($this->handler)(new CompleteTransfer($transferId->value));

        ($this->handler)(new CompleteTransfer($transferId->value));

        self::assertSame(
            TransferStatus::Completed,
            $this->transfers->get($transferId)->status(),
            'At-least-once delivery must not turn a redelivery into an error.',
        );
    }

    #[Test]
    public function it_translates_a_missing_transfer_into_an_application_exception(): void
    {
        $this->expectException(TransferDoesNotExist::class);

        ($this->handler)(new CompleteTransfer(TransferId::generate()->value));
    }

    private function givenInitiatedTransfer(): TransferId
    {
        $transferId = TransferId::generate();

        $this->transfers->save(Transfer::initiate(
            $transferId,
            WalletId::generate(),
            WalletId::generate(),
            Points::of(10),
            new \DateTimeImmutable('2026-01-01 12:00:00'),
        ));

        return $transferId;
    }
}
