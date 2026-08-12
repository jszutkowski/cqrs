import axios from 'axios';

import { token } from '../auth/token';
import type {
  LoginResponse,
  TransferSummary,
  WalletDetails,
  WalletSummary,
} from './types';

const http = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? 'http://localhost:8080',
  timeout: 5000,
  headers: { 'Content-Type': 'application/json' },
});

http.interceptors.request.use((config) => {
  const current = token.get();

  if (current !== null) {
    config.headers.Authorization = `Bearer ${current}`;
  }

  return config;
});

// The refresh-token flow was dropped along with its bundle: a demo with a
// single in-memory user gains nothing from it, and silently renewing a token
// hides authentication problems rather than solving them.
http.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401 && window.location.pathname !== '/login') {
      token.clear();
      window.location.assign('/login');
    }

    return Promise.reject(error);
  },
);

export const api = {
  login: async (username: string, password: string): Promise<string> => {
    const { data } = await http.post<LoginResponse>('/api/login', { username, password });

    return data.token;
  },

  listWallets: async (): Promise<WalletSummary[]> => {
    const { data } = await http.get<WalletSummary[]>('/api/wallets');

    return data;
  },

  getWallet: async (walletId: string): Promise<WalletDetails> => {
    const { data } = await http.get<WalletDetails>(`/api/wallets/${walletId}`);

    return data;
  },

  createWallet: async (): Promise<string> => {
    const { data } = await http.post<{ walletId: string }>('/api/wallets');

    return data.walletId;
  },

  addPoints: async (walletId: string, points: number): Promise<void> => {
    await http.post(`/api/wallets/${walletId}/points`, { points });
  },

  initiateTransfer: async (
    sourceWalletId: string,
    targetWalletId: string,
    points: number,
  ): Promise<string> => {
    const { data } = await http.post<{ transferId: string }>('/api/transfers', {
      sourceWalletId,
      targetWalletId,
      points,
    });

    return data.transferId;
  },

  getTransfer: async (transferId: string): Promise<TransferSummary> => {
    const { data } = await http.get<TransferSummary>(`/api/transfers/${transferId}`);

    return data;
  },
};
