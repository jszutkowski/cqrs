<?php

declare(strict_types=1);

namespace App\Tests\Support\Shared\Stub;

use App\Shared\Application\Command\Command;
use App\Shared\Application\Command\CommandBus;

final class RecordingCommandBus implements CommandBus
{
    /**
     * @var list<Command>
     */
    private array $dispatched = [];

    public function dispatch(Command $command): void
    {
        $this->dispatched[] = $command;
    }

    /**
     * @return list<Command>
     */
    public function dispatchedCommands(): array
    {
        return $this->dispatched;
    }

    /**
     * @template T of Command
     *
     * @param class-string<T> $commandClass
     *
     * @return T|null
     */
    public function firstOf(string $commandClass): ?Command
    {
        foreach ($this->dispatched as $command) {
            if ($command instanceof $commandClass) {
                return $command;
            }
        }

        return null;
    }
}
