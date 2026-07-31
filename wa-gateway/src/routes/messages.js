const express = require('express');
const { SessionManager } = require('../services/sessionManager');
const { createLogger } = require('../utils/logger');

const router = express.Router();
const logger = createLogger();

router.post('/send-text', async (req, res) => {
    try {
        const { session, phone, text } = req.body;

        if (!session || !phone || !text) {
            return res.status(400).json({ error: 'session, phone, and text are required' });
        }

        const device = SessionManager.get(session);

        if (!device) {
            return res.status(404).json({ error: 'Session not found' });
        }

        if (device.getStatus() !== 'connected') {
            return res.status(400).json({ error: 'Device not connected', status: device.getStatus() });
        }

        const result = await device.sendText(phone, text);

        res.json({
            success: true,
            data: {
                message_id: result.key.id,
                status: 'sent',
            },
        });
    } catch (error) {
        logger.error({ error: error.message }, 'Failed to send text');
        res.status(500).json({ success: false, error: error.message });
    }
});

router.post('/send-image', async (req, res) => {
    try {
        const { session, phone, image_url, caption } = req.body;

        if (!session || !phone || !image_url) {
            return res.status(400).json({ error: 'session, phone, and image_url are required' });
        }

        const device = SessionManager.get(session);

        if (!device) {
            return res.status(404).json({ error: 'Session not found' });
        }

        const result = await device.sendImage(phone, image_url, caption);

        res.json({
            success: true,
            data: {
                message_id: result.key.id,
                status: 'sent',
            },
        });
    } catch (error) {
        logger.error({ error: error.message }, 'Failed to send image');
        res.status(500).json({ success: false, error: error.message });
    }
});

router.post('/send-document', async (req, res) => {
    try {
        const { session, phone, document_url, file_name } = req.body;

        if (!session || !phone || !document_url) {
            return res.status(400).json({ error: 'session, phone, and document_url are required' });
        }

        const device = SessionManager.get(session);

        if (!device) {
            return res.status(404).json({ error: 'Session not found' });
        }

        const result = await device.sendDocument(phone, document_url, file_name);

        res.json({
            success: true,
            data: {
                message_id: result.key.id,
                status: 'sent',
            },
        });
    } catch (error) {
        logger.error({ error: error.message }, 'Failed to send document');
        res.status(500).json({ success: false, error: error.message });
    }
});

module.exports = router;