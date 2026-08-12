import {
  Alert,
  Button,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  MenuItem,
  Stack,
  TextField,
} from '@mui/material';
import { useEffect, useState } from 'react';

import { api } from '../api/client';
import type { WalletSummary } from '../api/types';

interface Props {
  open: boolean;
  wallets: WalletSummary[];
  onClose: () => void;
  onSubmitted: () => void;
}

export default function TransferDialog({ open, wallets, onClose, onSubmitted }: Props) {
  const [source, setSource] = useState('');
  const [target, setTarget] = useState('');
  const [points, setPoints] = useState(50);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (open) {
      setSource(wallets[0]?.walletId ?? '');
      setTarget(wallets[1]?.walletId ?? '');
      setError(null);
    }
  }, [open, wallets]);

  const submit = async () => {
    setError(null);

    try {
      await api.initiateTransfer(source, target, points);
      onSubmitted();
      onClose();
    } catch {
      setError('The transfer was rejected. Check the wallets and the amount.');
    }
  };

  const sameWallet = source === target;

  return (
    <Dialog open={open} onClose={onClose} fullWidth maxWidth="sm">
      <DialogTitle>Transfer points</DialogTitle>

      <DialogContent>
        <Stack spacing={2} mt={1}>
          {error !== null && <Alert severity="error">{error}</Alert>}

          <TextField
            select
            label="From"
            value={source}
            onChange={(event) => setSource(event.target.value)}
          >
            {wallets.map((wallet) => (
              <MenuItem key={wallet.walletId} value={wallet.walletId}>
                {wallet.walletId} ({wallet.balance})
              </MenuItem>
            ))}
          </TextField>

          <TextField
            select
            label="To"
            value={target}
            onChange={(event) => setTarget(event.target.value)}
            error={sameWallet}
            helperText={sameWallet ? 'Pick a different wallet.' : ' '}
          >
            {wallets.map((wallet) => (
              <MenuItem key={wallet.walletId} value={wallet.walletId}>
                {wallet.walletId} ({wallet.balance})
              </MenuItem>
            ))}
          </TextField>

          <TextField
            label="Points"
            type="number"
            value={points}
            onChange={(event) => setPoints(Number(event.target.value))}
            slotProps={{ htmlInput: { min: 1 } }}
          />
        </Stack>
      </DialogContent>

      <DialogActions>
        <Button onClick={onClose}>Cancel</Button>
        <Button variant="contained" onClick={submit} disabled={sameWallet || points < 1}>
          Send
        </Button>
      </DialogActions>
    </Dialog>
  );
}
