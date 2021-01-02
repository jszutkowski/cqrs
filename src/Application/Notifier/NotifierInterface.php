<?php

declare(strict_types=1);

namespace App\Application\Notifier;

interface NotifierInterface
{
    public function notify(Event $event): void;
}
