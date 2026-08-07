const express = require('express');
const { SessionManager } = require('../services/sessionManager');
const { createLogger } = require('../utils/logger');

const router = express.Router();
const logger = createLogger();

router.post('/', async (req, res) => {
    try {
        const { session_name } = req.body;

        if (!session_name) {
            return res.status(400).json({ error: 'session_name is required' });
        }

        // Idempoten: jika session sudah ada, sambungkan kembali dan kembalikan status/QR.
        const exists = SessionManager.has(session_name);
        const device = exists
            ? SessionManager.get(session_name)
            : SessionManager.getOrCreate(session_name);

        await device.connect();

        res.status(exists ? 200 : 201).json({
            success: true,
            data: {
                session: session_name,
                status: device.getStatus(),
                qr_code: device.qrCode,
            },
        });
    } catch (error) {
        logger.error({ error: error.message }, 'Failed to create device');
        res.status(500).json({ success: false, error: error.message });
    }
});

router.delete('/:sessionName', async (req, res) => {
    try {
        const { sessionName } = req.params;

        if (!SessionManager.has(sessionName)) {
            return res.status(404).json({ error: 'Session not found' });
        }

        await SessionManager.remove(sessionName);

        res.json({ success: true });
    } catch (error) {
        logger.error({ error: error.message }, 'Failed to delete device');
        res.status(500).json({ success: false, error: error.message });
    }
});

router.post('/:sessionName/connect', async (req, res) => {
    try {
        const { sessionName } = req.params;
        const device = SessionManager.getOrCreate(sessionName);
        await device.connect();

        res.json({
            success: true,
            data: {
                session: sessionName,
                status: device.getStatus(),
                qr_code: device.qrCode,
            },
        });
    } catch (error) {
        logger.error({ error: error.message }, 'Failed to connect');
        res.status(500).json({ success: false, error: error.message });
    }
});

router.post('/:sessionName/disconnect', async (req, res) => {
    try {
        const { sessionName } = req.params;
        const device = SessionManager.get(sessionName);

        if (!device) {
            return res.status(404).json({ error: 'Session not found' });
        }

        await device.disconnect();

        res.json({ success: true });
    } catch (error) {
        logger.error({ error: error.message }, 'Failed to disconnect');
        res.status(500).json({ success: false, error: error.message });
    }
});

router.post('/:sessionName/logout', async (req, res) => {
    try {
        const { sessionName } = req.params;
        const device = SessionManager.get(sessionName);

        if (!device) {
            return res.status(404).json({ error: 'Session not found' });
        }

        await device.logout();

        res.json({ success: true });
    } catch (error) {
        logger.error({ error: error.message }, 'Failed to logout');
        res.status(500).json({ success: false, error: error.message });
    }
});

router.get('/:sessionName/status', async (req, res) => {
    try {
        const { sessionName } = req.params;
        const device = SessionManager.get(sessionName);

        if (!device) {
            return res.status(404).json({ error: 'Session not found' });
        }

        res.json({
            success: true,
            data: {
                session: sessionName,
                status: device.getStatus(),
                phone_number: device.phoneNumber,
                profile_name: device.profileName,
                last_seen: device.lastSeen,
            },
        });
    } catch (error) {
        logger.error({ error: error.message }, 'Failed to get status');
        res.status(500).json({ success: false, error: error.message });
    }
});

router.get('/:sessionName/qr', async (req, res) => {
    try {
        const { sessionName } = req.params;
        const device = SessionManager.get(sessionName);

        if (!device) {
            return res.status(404).json({ error: 'Session not found' });
        }

        // QR muncul asinkron — tunggu hingga tersedia (maks ~12 detik).
        const deadline = Date.now() + 12000;
        while (!device.qrCode && device.getStatus() !== 'connected' && Date.now() < deadline) {
            await new Promise((resolve) => setTimeout(resolve, 700));
        }

        res.json({
            success: true,
            data: {
                session: sessionName,
                qr_code: device.qrCode,
                status: device.getStatus(),
            },
        });
    } catch (error) {
        logger.error({ error: error.message }, 'Failed to get QR');
        res.status(500).json({ success: false, error: error.message });
    }
});

router.get('/', async (req, res) => {
    try {
        const statuses = SessionManager.getAllStatus();

        res.json({
            success: true,
            data: statuses,
        });
    } catch (error) {
        logger.error({ error: error.message }, 'Failed to list devices');
        res.status(500).json({ success: false, error: error.message });
    }
});

module.exports = router;