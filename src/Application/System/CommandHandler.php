<?php

declare(strict_types=1);

namespace App\Application\System;

interface CommandHandler
{
    public function __invoke(Command $command): void;
}
