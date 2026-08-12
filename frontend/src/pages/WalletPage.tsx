import ArrowBackIcon from '@mui/icons-material/ArrowBack';
import {
  Box,
  Button,
  Chip,
  Container,
  Paper,
  Stack,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  TextField,
  Typography,
} from '@mui/material';
import { useCallback, useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';

import { api } from '../api/client';
import type { WalletDetails } from '../api/types';
import {
  LiveEvent,
  useLiveUpdates,
  type BalanceChangedEvent,
} from '../sockets/useLiveUpdates';

export default function WalletPage() {
  const { walletId = '' } = useParams();
  const [wallet, setWallet] = useState<WalletDetails | null>(null);
  const [points, setPoints] = useState(100);

  useEffect(() => {
    void api.getWallet(walletId).then(setWallet);
  }, [walletId]);

  const subscribe = useCallback(
    (socket: Parameters<Parameters<typeof useLiveUpdates>[0]>[0]) => {
      socket.emit('subscribe.wallet', walletId);

      socket.on(LiveEvent.BalanceChanged, (event: BalanceChangedEvent) => {
        setWallet((current) =>
          current === null
            ? current
            : {
                ...current,
                balance: event.balance,
                history: [
                  {
                    amount: event.amount,
                    registeredAt: event.registeredAt,
                    transferId: event.transferId,
                  },
                  ...current.history,
                ],
              },
        );
      });
    },
    [walletId],
  );

  useLiveUpdates(subscribe, walletId !== '');

  const addPoints = async () => {
    await api.addPoints(walletId, points);
  };

  if (wallet === null) {
    return (
      <Container maxWidth="md" sx={{ mt: 4 }}>
        <Typography color="text.secondary">Loading…</Typography>
      </Container>
    );
  }

  return (
    <Container maxWidth="md" sx={{ mt: 4 }}>
      <Button component={Link} to="/" startIcon={<ArrowBackIcon />} sx={{ mb: 2 }}>
        All wallets
      </Button>

      <Paper sx={{ p: 3, mb: 3 }}>
        <Typography variant="overline" color="text.secondary">
          Wallet
        </Typography>
        <Typography sx={{ fontFamily: 'monospace' }} gutterBottom>
          {wallet.walletId}
        </Typography>

        <Typography variant="h4">{wallet.balance}</Typography>

        <Stack direction="row" spacing={2} mt={3}>
          <TextField
            label="Points"
            type="number"
            size="small"
            value={points}
            onChange={(event) => setPoints(Number(event.target.value))}
            slotProps={{ htmlInput: { min: 1 } }}
          />
          <Button variant="contained" onClick={addPoints} disabled={points < 1}>
            Add points
          </Button>
        </Stack>
      </Paper>

      <TableContainer component={Paper}>
        <Table size="small">
          <TableHead>
            <TableRow>
              <TableCell>When</TableCell>
              <TableCell align="right">Change</TableCell>
              <TableCell>Transfer</TableCell>
            </TableRow>
          </TableHead>
          <TableBody>
            {wallet.history.map((entry, index) => (
              <TableRow key={`${entry.registeredAt}-${index}`}>
                <TableCell>{new Date(entry.registeredAt).toLocaleString()}</TableCell>
                <TableCell align="right">
                  <Chip
                    size="small"
                    label={entry.amount > 0 ? `+${entry.amount}` : entry.amount}
                    color={entry.amount > 0 ? 'success' : 'warning'}
                    variant="outlined"
                  />
                </TableCell>
                <TableCell sx={{ fontFamily: 'monospace', fontSize: 12 }}>
                  {entry.transferId ?? '—'}
                </TableCell>
              </TableRow>
            ))}

            {wallet.history.length === 0 && (
              <TableRow>
                <TableCell colSpan={3}>
                  <Box py={3} textAlign="center" color="text.secondary">
                    No movements yet.
                  </Box>
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      </TableContainer>
    </Container>
  );
}
