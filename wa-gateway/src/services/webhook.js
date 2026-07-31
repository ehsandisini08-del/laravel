const { createLogger } = require('../utils/logger');

const logger = createLogger();
const WEBHOOK_URL = process.env.WEBHOOK_URL;
const WEBHOOK_SECRET = process.env.WEBHOOK_SECRET;

async function sendWebhook(event, sessionName, data) {
    if (!WEBHOOK_URL) {
        return;
    }

    try {
        const payload = {
            event,
            session: sessionName,
            data,
            timestamp: new Date().toISOString(),
        };

        const response = await fetch(WEBHOOK_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Webhook-Secret': WEBHOOK_SECRET,
            },
            body: JSON.stringify(payload),
        });

        if (!response.ok) {
            logger.warn({ event, sessionName, status: response.status }, 'Webhook delivery failed');
        }
    } catch (error) {
        logger.error({ event, sessionName, error: error.message }, 'Webhook delivery error');
    }
}

module.exports = { sendWebhook };