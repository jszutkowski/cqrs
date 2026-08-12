<?php

declare(strict_types=1);

namespace App\Loyalty\Application\Notification;

enum NotificationName: string
{
    case WalletCreated = 'wallet_created';
    case BalanceChanged = 'balance_changed';
    case TransferSettled = 'transfer_settled';
}
