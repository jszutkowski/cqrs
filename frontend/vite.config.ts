import react from '@vitejs/plugin-react';
import { defineConfig } from 'vite';

export default defineConfig({
  plugins: [react()],
  server: {
    host: '0.0.0.0',
    port: 5173,
    // The container filesystem is bind-mounted, so inotify events do not always
    // reach the dev server; polling keeps hot reload working inside Docker.
    watch: { usePolling: true },
  },
});
