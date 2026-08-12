const STORAGE_KEY = 'token';

export const token = {
  get: (): string | null => localStorage.getItem(STORAGE_KEY),
  set: (value: string): void => localStorage.setItem(STORAGE_KEY, value),
  clear: (): void => localStorage.removeItem(STORAGE_KEY),
};
