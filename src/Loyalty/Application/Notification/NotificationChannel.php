<?php

declare(strict_types=1);

namespace App\Loyalty\Application\Notification;

enum NotificationChannel: string
{
    case Wallets = 'wallets';
    case Wallet = 'wallet';

    public function forWallet(string $walletId): string
    {
        return \sprintf('%s:%s', $this->value, $walletId);
    }
}
