export interface WalletSummary {
  walletId: string;
  balance: number;
}

export interface PointsEntry {
  amount: number;
  registeredAt: string;
  transferId: string | null;
}

export interface WalletDetails {
  walletId: string;
  balance: number;
  history: PointsEntry[];
}

export type TransferStatus = 'initiated' | 'completed' | 'failed';

export interface TransferSummary {
  transferId: string;
  sourceWalletId: string;
  targetWalletId: string;
  points: number;
  status: TransferStatus;
  failureReason: string | null;
}

export interface LoginResponse {
  token: string;
}
