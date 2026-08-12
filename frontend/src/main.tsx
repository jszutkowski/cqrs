import { CssBaseline, ThemeProvider, createTheme } from '@mui/material';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';

import { token } from './auth/token';
import LoginPage from './pages/LoginPage';
import WalletPage from './pages/WalletPage';
import WalletsPage from './pages/WalletsPage';

const theme = createTheme({ colorSchemes: { dark: true } });

function RequireAuth({ children }: { children: React.ReactNode }) {
  return token.get() !== null ? <>{children}</> : <Navigate to="/login" replace />;
}

const container = document.getElementById('root');

if (container === null) {
  throw new Error('The #root element is missing from index.html.');
}

createRoot(container).render(
  <StrictMode>
    <ThemeProvider theme={theme}>
      <CssBaseline />
      <BrowserRouter>
        <Routes>
          <Route path="/login" element={<LoginPage />} />
          <Route
            path="/"
            element={
              <RequireAuth>
                <WalletsPage />
              </RequireAuth>
            }
          />
          <Route
            path="/wallets/:walletId"
            element={
              <RequireAuth>
                <WalletPage />
              </RequireAuth>
            }
          />
        </Routes>
      </BrowserRouter>
    </ThemeProvider>
  </StrictMode>,
);
