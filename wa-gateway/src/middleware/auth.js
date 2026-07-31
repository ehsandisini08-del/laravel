const { createLogger } = require('../utils/logger');

const logger = createLogger();
const API_TOKEN = process.env.API_TOKEN;

function authMiddleware(req, res, next) {
    if (req.path === '/health') {
        return next();
    }

    const authHeader = req.headers.authorization;

    if (!authHeader || !authHeader.startsWith('Bearer ')) {
        logger.warn('Missing or invalid Authorization header');
        return res.status(401).json({ error: 'Unauthorized' });
    }

    const token = authHeader.substring(7);

    if (token !== API_TOKEN) {
        logger.warn('Invalid API token');
        return res.status(403).json({ error: 'Forbidden' });
    }

    next();
}

module.exports = { authMiddleware };