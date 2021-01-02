const express = require('express');
const app = express();

const config = require('./config');
const http = require('http');
const socketIo = require("socket.io");
const redis = require('redis');

const server = http.createServer(app);
const io = socketIo(server, {
    cors: {
        origin: 'http://localhost:8080'
    }
});

//@todo: rewrite to Typescript
//@todo: add autoreloading

server.listen(config.socket.port);

io.on('connection', socket => {
    console.log(`Connected ${socket.id}`);
    socket.emit('connected');

    const redisClient = getRedisClient();
    let subscribedWalletId = '';

    redisClient.on('message', (channel, payload) => {
        const data = JSON.parse(payload);

        console.log('Redis event `%s` to channel `%s`', data.eventName, channel);
        console.log(data);
        console.log('----------------');

        switch (channel) {
            case 'wallets':
                console.log('sending event to wallets %s', data.eventName);
                socket.emit(data.eventName, data.payload);
                break;
            case `wallet:${subscribedWalletId}`:
                console.log('sending event to wallet %s -> %s', data.payload.walletId, data.eventName);
                socket.emit(data.eventName, data.payload);
                break;
            default:
                console.log('unhandled channel %s', channel);
        }
    });

    socket.on('subscribe.wallets', () => {
        console.log('Subscribe walletS');
        redisClient.subscribe('wallets')
    });

    socket.on('subscribe.wallet', walletId => {
        console.log(`Subscribed wallet ${walletId}`);
        subscribedWalletId = walletId;
        redisClient.subscribe(`wallet:${walletId}`);
    });

    socket.on('disconnect', () => {
        console.log('Disconnected');
        redisClient.quit();
    })
});

const getRedisClient = () => {
    console.log(`Connect to redis ${config.redis.host}:${config.redis.port}, env: ${config.env}`);
    const redisClient = redis.createClient(config.redis.port, config.redis.host);

    redisClient
        .on('error', function (err) {
            console.error('Redis error: ', err);
            return null;
        });

    return redisClient;
}
