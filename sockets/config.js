module.exports = {
    env: process.env.ENVIRONMENT__TAG || 'local',
    redis: {
        host: process.env.REDIS__HOST || '127.0.0.1',
        port: process.env.REDIS__PORT || 6379
    },
    socket: {
        host: process.env.SOCKET__HOST || '127.0.0.1',
        port: process.env.SOCKET__INTERNAL__PORT || 5000
    },
};
