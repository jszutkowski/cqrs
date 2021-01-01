<?php

namespace App\Application\System;

interface CommandBusInterface
{
    public function handle(Command $command): void;
}
