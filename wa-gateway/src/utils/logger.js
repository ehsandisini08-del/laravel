const pino = require('pino');

function createLogger() {
    return pino({
        level: process.env.LOG_LEVEL || 'info',
        transport: {
            target: 'pino-pretty',
            options: {
                colorize: true,
                translateTime: 'SYS:yyyy-mm-dd HH:MM:ss',
            },
        },
    });
}

module.exports = { createLogger };