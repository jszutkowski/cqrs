import { useEffect } from 'react';
import { io, type Socket } from 'socket.io-client';

/**
 * Notification names mirror App\Loyalty\Application\Notification\NotificationName.
 */
export const LiveEvent = {
  WalletCreated: 'wallet_created',
  BalanceChanged: 'balance_changed',
  TransferSettled: 'transfer_settled',
} as const;

export interface WalletCreatedEvent {
  walletId: string;
  balance: number;
}

export interface BalanceChangedEvent {
  walletId: string;
  amount: number;
  balance: number;
  transferId: string | null;
  registeredAt: string;
}

export interface TransferSettledEvent {
  transferId: string;
  status: 'completed' | 'failed';
  failureReason: string | null;
}

type Subscription = (socket: Socket) => void;

/**
 * Opens one socket for the lifetime of the component and closes it on unmount.
 *
 * `subscribe` is intentionally called once per connection rather than on every
 * render: re-running it would stack duplicate listeners and make each update
 * apply several times.
 */
export function useLiveUpdates(subscribe: Subscription, enabled = true): void {
  useEffect(() => {
    if (!enabled) {
      return;
    }

    const socket = io(import.meta.env.VITE_SOCKETS_URL ?? 'http://localhost:3001');

    subscribe(socket);

    return () => {
      socket.disconnect();
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [enabled]);
}
