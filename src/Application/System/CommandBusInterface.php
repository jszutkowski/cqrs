<?php

namespace App\Application\System;

interface CommandBusInterface
{
    public function dispatch(Command $command): void;
}
