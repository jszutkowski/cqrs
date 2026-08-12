import { Alert, Box, Button, Container, Paper, Stack, TextField, Typography } from '@mui/material';
import { useState, type FormEvent } from 'react';
import { useNavigate } from 'react-router-dom';

import { api } from '../api/client';
import { token } from '../auth/token';

export default function LoginPage() {
  const navigate = useNavigate();
  const [username, setUsername] = useState('admin');
  const [password, setPassword] = useState('admin1');
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    setBusy(true);
    setError(null);

    try {
      token.set(await api.login(username, password));
      navigate('/');
    } catch {
      setError('Those credentials were not accepted.');
    } finally {
      setBusy(false);
    }
  };

  return (
    <Container maxWidth="xs" sx={{ mt: 8 }}>
      <Paper sx={{ p: 4 }}>
        <Typography variant="h5" gutterBottom>
          Sign in
        </Typography>

        <Box component="form" onSubmit={submit}>
          <Stack spacing={2}>
            {error !== null && <Alert severity="error">{error}</Alert>}

            <TextField
              label="Username"
              value={username}
              onChange={(event) => setUsername(event.target.value)}
              autoFocus
              fullWidth
            />
            <TextField
              label="Password"
              type="password"
              value={password}
              onChange={(event) => setPassword(event.target.value)}
              fullWidth
            />

            <Button type="submit" variant="contained" disabled={busy}>
              {busy ? 'Signing in…' : 'Sign in'}
            </Button>
          </Stack>
        </Box>
      </Paper>
    </Container>
  );
}
