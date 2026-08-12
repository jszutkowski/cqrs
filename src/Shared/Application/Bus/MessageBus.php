<?php

declare(strict_types=1);

namespace App\Shared\Application\Bus;

/**
 * Names of the two Messenger buses.
 *
 * Declared as constants rather than repeated literals because every handler
 * names its bus in an attribute: a typo in one of them silently registers the
 * handler nowhere, and nothing fails until the message it was meant to handle
 * goes unanswered at runtime. Referencing a constant turns that into a
 * compile-time error.
 *
 * config/packages/messenger.yaml pulls the same constants through !php/const,
 * so the definition and its twenty-odd usages cannot drift apart.
 */
final class MessageBus
{
    public const string COMMAND = 'command.bus';
    public const string EVENT = 'event.bus';

    private function __construct()
    {
    }
}
