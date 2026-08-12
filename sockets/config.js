export const config = {
  redisUrl: process.env.REDIS_URL || 'redis://127.0.0.1:6379',
  port: Number(process.env.PORT || 3001),
  corsOrigin: process.env.CORS_ORIGIN || 'http://localhost:5173',
};
