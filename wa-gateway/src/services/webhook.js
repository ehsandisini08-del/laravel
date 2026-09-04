const { createLogger } = require('../utils/logger');

const logger = createLogger();
const WEBHOOK_URL = process.env.WEBHOOK_URL;
const WEBHOOK_SECRET = process.env.WEBHOOK_SECRET;

async function sendWebhook(event, sessionName, data) {
    if (!WEBHOOK_URL) {
        return;
    }

    const payload = {
        event,
        session: sessionName,
        data,
        timestamp: new Date().toISOString(),
    };

    // Retry logic (3 attempts)
    for (let attempt = 1; attempt <= 3; attempt++) {
        try {
            const response = await fetch(WEBHOOK_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Webhook-Secret': WEBHOOK_SECRET,
                },
                body: JSON.stringify(payload),
                signal: AbortSignal.timeout(5000), // 5s timeout
            });

            if (response.ok) {
                logger.debug({ event, sessionName, attempt }, 'Webhook sent successfully');
                return;
            }

            logger.warn({ event, sessionName, attempt, status: response.status }, 'Webhook failed, retrying...');
        } catch (error) {
            logger.error({ event, sessionName, attempt, error: error.message }, 'Webhook error');
        }

        // Wait before retry (exponential backoff)
        if (attempt < 3) {
            await new Promise(resolve => setTimeout(resolve, 1000 * attempt));
        }
    }

    // All retries failed, log it
    logger.error({ event, sessionName }, 'Webhook failed after 3 attempts');
}

module.exports = { sendWebhook };