import { createServer } from 'node:http';
import { createClient } from 'redis';
import { Server } from 'socket.io';

import { config } from './config.js';

/**
 * Bridges Redis pub/sub to the browser.
 *
 * Each socket gets its own Redis subscriber and tracks which channels it joined,
 * so a client only ever receives the wallet it is actually looking at. The
 * previous version kept a single mutable "subscribed wallet" variable, which
 * leaked one client's wallet updates into another's stream once two people had
 * a wallet page open at the same time.
 */
const server = createServer((request, response) => {
  if (request.url === '/health') {
    response.writeHead(200, { 'Content-Type': 'application/json' });
    response.end(JSON.stringify({ status: 'ok' }));

    return;
  }

  response.writeHead(404);
  response.end();
});

const io = new Server(server, {
  cors: { origin: config.corsOrigin },
});

io.on('connection', async (socket) => {
  const subscriber = createClient({ url: config.redisUrl });

  subscriber.on('error', (error) => console.error('Redis error:', error.message));

  await subscriber.connect();

  const joined = new Set();

  const listen = async (channel) => {
    if (joined.has(channel)) {
      return;
    }

    joined.add(channel);

    await subscriber.subscribe(channel, (message) => {
      const { name, payload } = JSON.parse(message);

      socket.emit(name, payload);
    });
  };

  socket.on('subscribe.wallets', () => listen('wallets'));
  socket.on('subscribe.wallet', (walletId) => listen(`wallet:${walletId}`));

  socket.on('disconnect', async () => {
    await subscriber.quit().catch(() => {});
  });

  socket.emit('connected');
});

server.listen(config.port, () => {
  console.log(`Socket bridge listening on :${config.port}`);
});
