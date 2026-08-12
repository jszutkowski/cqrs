<?php

declare(strict_types=1);

namespace App\Shared\Application\Event;

/**
 * A fact about how a use case concluded, as opposed to a change of aggregate
 * state.
 *
 * Domain events are recorded in the event store and replayed to rebuild an
 * aggregate. A process event is neither: "the debit was rejected" changes no
 * wallet, so writing it to a wallet's stream would corrupt the meaning of that
 * stream. It exists purely so a process manager can learn the outcome of a
 * command it issued.
 */
interface ProcessEvent
{
    public function transferId(): string;
}
