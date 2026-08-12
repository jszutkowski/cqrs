import AddIcon from '@mui/icons-material/Add';
import SwapHorizIcon from '@mui/icons-material/SwapHoriz';
import {
  Alert,
  Box,
  Button,
  Container,
  Paper,
  Snackbar,
  Stack,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Typography,
} from '@mui/material';
import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';

import { api } from '../api/client';
import type { WalletSummary } from '../api/types';
import TransferDialog from '../components/TransferDialog';
import {
  LiveEvent,
  useLiveUpdates,
  type BalanceChangedEvent,
  type TransferSettledEvent,
  type WalletCreatedEvent,
} from '../sockets/useLiveUpdates';

export default function WalletsPage() {
  const [wallets, setWallets] = useState<WalletSummary[]>([]);
  const [transferOpen, setTransferOpen] = useState(false);
  const [notice, setNotice] = useState<string | null>(null);

  useEffect(() => {
    void api.listWallets().then(setWallets);
  }, []);

  const subscribe = useCallback((socket: Parameters<Parameters<typeof useLiveUpdates>[0]>[0]) => {
    socket.emit('subscribe.wallets');

    socket.on(LiveEvent.WalletCreated, (event: WalletCreatedEvent) => {
      setWallets((current) =>
        current.some((wallet) => wallet.walletId === event.walletId)
          ? current
          : [...current, { walletId: event.walletId, balance: event.balance }],
      );
    });

    socket.on(LiveEvent.BalanceChanged, (event: BalanceChangedEvent) => {
      setWallets((current) =>
        current.map((wallet) =>
          wallet.walletId === event.walletId ? { ...wallet, balance: event.balance } : wallet,
        ),
      );
    });

    socket.on(LiveEvent.TransferSettled, (event: TransferSettledEvent) => {
      setNotice(
        event.status === 'completed'
          ? 'Transfer completed.'
          : `Transfer failed: ${event.failureReason ?? 'unknown reason'}`,
      );
    });
  }, []);

  useLiveUpdates(subscribe);

  const createWallet = async () => {
    await api.createWallet();
  };

  return (
    <Container maxWidth="md" sx={{ mt: 4 }}>
      <Stack direction="row" justifyContent="space-between" alignItems="center" mb={2}>
        <Typography variant="h5">Wallets</Typography>

        <Stack direction="row" spacing={1}>
          <Button
            variant="outlined"
            startIcon={<SwapHorizIcon />}
            onClick={() => setTransferOpen(true)}
            disabled={wallets.length < 2}
          >
            Transfer
          </Button>
          <Button variant="contained" startIcon={<AddIcon />} onClick={createWallet}>
            New wallet
          </Button>
        </Stack>
      </Stack>

      <TableContainer component={Paper}>
        <Table>
          <TableHead>
            <TableRow>
              <TableCell>Identifier</TableCell>
              <TableCell align="right">Balance</TableCell>
              <TableCell align="center">Actions</TableCell>
            </TableRow>
          </TableHead>
          <TableBody>
            {wallets.map((wallet) => (
              <TableRow key={wallet.walletId} hover>
                <TableCell sx={{ fontFamily: 'monospace' }}>{wallet.walletId}</TableCell>
                <TableCell align="right">{wallet.balance}</TableCell>
                <TableCell align="center">
                  <Link to={`/wallets/${wallet.walletId}`}>Open</Link>
                </TableCell>
              </TableRow>
            ))}

            {wallets.length === 0 && (
              <TableRow>
                <TableCell colSpan={3}>
                  <Box py={3} textAlign="center" color="text.secondary">
                    No wallets yet. Create one to get started.
                  </Box>
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      </TableContainer>

      <TransferDialog
        open={transferOpen}
        wallets={wallets}
        onClose={() => setTransferOpen(false)}
        onSubmitted={() => setNotice('Transfer accepted; the saga is running.')}
      />

      <Snackbar
        open={notice !== null}
        autoHideDuration={6000}
        onClose={() => setNotice(null)}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'center' }}
      >
        <Alert severity="info" onClose={() => setNotice(null)}>
          {notice}
        </Alert>
      </Snackbar>
    </Container>
  );
}
