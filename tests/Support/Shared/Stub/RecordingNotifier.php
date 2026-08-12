<?php

declare(strict_types=1);

namespace App\Tests\Support\Shared\Stub;

use App\Shared\Application\Notification\Notification;
use App\Shared\Application\Notification\Notifier;

final class RecordingNotifier implements Notifier
{
    /**
     * @var list<Notification>
     */
    private array $notifications = [];

    public function notify(Notification $notification): void
    {
        $this->notifications[] = $notification;
    }

    /**
     * @return list<Notification>
     */
    public function notifications(): array
    {
        return $this->notifications;
    }
}
