const express = require('express');
const router = express.Router();
const { SessionManager } = require('../services/sessionManager');
const { HealthMonitor } = require('../services/healthMonitor');
const { SessionBackup } = require('../services/sessionBackup');
const { MessageQueue } = require('../services/messageQueue');
const { ConnectionStateManager } = require('../services/connectionStateManager');
const { AlertManager } = require('../utils/alerts');
const { createLogger } = require('../utils/logger');

const logger = createLogger();

// GET /monitoring/status
// Return current status of all devices
router.get('/status', (req, res) => {
    try {
        const devices = SessionManager.getAllDevices();
        
        const status = devices.map(device => ({
            sessionName: device.sessionName,
            status: device.getStatus(),
            phoneNumber: device.phoneNumber,
            profileName: device.profileName,
            lastSeen: device.lastSeen,
            isHealthy: device._isHealthy,
            qrCode: device.qrCode,
            reconnectAttempts: device._reconnectAttempts,
        }));

        res.json({
            success: true,
            devices: status,
            timestamp: new Date().toISOString(),
        });
    } catch (error) {
        logger.error({ error: error.message }, 'Failed to get status');
        res.status(500).json({
            success: false,
            error: error.message,
        });
    }
});

// GET /monitoring/statistics/:sessionName
// Return detailed statistics for a session
router.get('/statistics/:sessionName', (req, res) => {
    try {
        const { sessionName } = req.params;
        
        const stats = ConnectionStateManager.getStatistics(sessionName);
        
        if (!stats) {
            return res.status(404).json({
                success: false,
                error: 'Session not found',
            });
        }

        res.json({
            success: true,
            statistics: stats,
        });
    } catch (error) {
        logger.error({ error: error.message }, 'Failed to get statistics');
        res.status(500).json({
            success: false,
            error: error.message,
        });
    }
});

// GET /monitoring/statistics
// Return statistics for all sessions
router.get('/statistics', (req, res) => {
    try {
        const allStats = ConnectionStateManager.getAllStatistics();

        res.json({
            success: true,
            statistics: allStats,
        });
    } catch (error) {
        logger.error({ error: error.message }, 'Failed to get all statistics');
        res.status(500).json({
            success: false,
            error: error.message,
        });
    }
});

// GET /monitoring/history/:sessionName
// Return connection history for a session
router.get('/history/:sessionName', (req, res) => {
    try {
        const { sessionName } = req.params;
        const limit = parseInt(req.query.limit || 100);
        
        const history = ConnectionStateManager.getConnectionHistory(sessionName, limit);

        res.json({
            success: true,
            sessionName,
            history,
        });
    } catch (error) {
        logger.error({ error: error.message }, 'Failed to get history');
        res.status(500).json({
            success: false,
            error: error.message,
        });
    }
});

// GET /monitoring/alerts
// Return active alerts
router.get('/alerts', (req, res) => {
    try {
        const activeAlerts = AlertManager.getActiveAlerts();
        const connectionAlerts = ConnectionStateManager.getActiveAlerts();
        const alertStats = AlertManager.getAlertStats();

        res.json({
            success: true,
            alerts: [...activeAlerts, ...connectionAlerts],
            stats: alertStats,
        });
    } catch (error) {
        logger.error({ error: error.message }, 'Failed to get alerts');
        res.status(500).json({
            success: false,
            error: error.message,
        });
    }
});

// GET /monitoring/queue/:sessionName
// Return message queue status for a session
router.get('/queue/:sessionName', (req, res) => {
    try {
        const { sessionName } = req.params;
        
        const device = SessionManager.get(sessionName);
        
        if (!device) {
            return res.status(404).json({
                success: false,
                error: 'Session not found',
            });
        }

        if (!device._messageQueue) {
            return res.json({
                success: true,
                queue: {
                    sessionName,
                    total: 0,
                    pending: 0,
                    processing: 0,
                    failed: 0,
                    messages: [],
                },
            });
        }

        const queueStatus = device._messageQueue.getQueueStatus();

        res.json({
            success: true,
            queue: queueStatus,
        });
    } catch (error) {
        logger.error({ error: error.message }, 'Failed to get queue status');
        res.status(500).json({
            success: false,
            error: error.message,
        });
    }
});

// GET /monitoring/queue
// Return queue status for all sessions
router.get('/queue', (req, res) => {
    try {
        const queueStats = MessageQueue.getAllQueueStats();

        res.json({
            success: true,
            queues: queueStats,
        });
    } catch (error) {
        logger.error({ error: error.message }, 'Failed to get all queue stats');
        res.status(500).json({
            success: false,
            error: error.message,
        });
    }
});

// GET /monitoring/health
// Return health monitoring report
router.get('/health', (req, res) => {
    try {
        const healthReport = HealthMonitor.getHealthReport();

        res.json({
            success: true,
            health: healthReport,
        });
    } catch (error) {
        logger.error({ error: error.message }, 'Failed to get health report');
        res.status(500).json({
            success: false,
            error: error.message,
        });
    }
});

// GET /monitoring/backups/:sessionName
// List available backups for a session
router.get('/backups/:sessionName', (req, res) => {
    try {
        const { sessionName } = req.params;
        
        const backups = SessionBackup.listBackups(sessionName);

        res.json({
            success: true,
            sessionName,
            backups,
        });
    } catch (error) {
        logger.error({ error: error.message }, 'Failed to list backups');
        res.status(500).json({
            success: false,
            error: error.message,
        });
    }
});

// GET /monitoring/backups
// Get backup statistics for all sessions
router.get('/backups', (req, res) => {
    try {
        const backupStats = SessionBackup.getBackupStats();

        res.json({
            success: true,
            backups: backupStats,
        });
    } catch (error) {
        logger.error({ error: error.message }, 'Failed to get backup stats');
        res.status(500).json({
            success: false,
            error: error.message,
        });
    }
});

// POST /monitoring/reconnect/:sessionName
// Force manual reconnect for a device
router.post('/reconnect/:sessionName', async (req, res) => {
    try {
        const { sessionName } = req.params;
        
        const device = SessionManager.get(sessionName);
        
        if (!device) {
            return res.status(404).json({
                success: false,
                error: 'Session not found',
            });
        }

        logger.info({ sessionName }, 'Manual reconnect triggered');

        await device.forceReconnect();

        res.json({
            success: true,
            message: 'Reconnect initiated',
            sessionName,
        });
    } catch (error) {
        logger.error({ error: error.message }, 'Failed to reconnect');
        res.status(500).json({
            success: false,
            error: error.message,
        });
    }
});

// POST /monitoring/backup/:sessionName
// Trigger manual backup for a session
router.post('/backup/:sessionName', async (req, res) => {
    try {
        const { sessionName } = req.params;
        
        const device = SessionManager.get(sessionName);
        
        if (!device) {
            return res.status(404).json({
                success: false,
                error: 'Session not found',
            });
        }

        logger.info({ sessionName }, 'Manual backup triggered');

        const success = await SessionBackup.backupSession(sessionName);

        if (success) {
            res.json({
                success: true,
                message: 'Backup created successfully',
                sessionName,
            });
        } else {
            res.status(500).json({
                success: false,
                error: 'Backup failed',
            });
        }
    } catch (error) {
        logger.error({ error: error.message }, 'Failed to backup');
        res.status(500).json({
            success: false,
            error: error.message,
        });
    }
});

// POST /monitoring/restore/:sessionName
// Restore session from backup
router.post('/restore/:sessionName', async (req, res) => {
    try {
        const { sessionName } = req.params;
        const { backupIndex = 0 } = req.body;
        
        const device = SessionManager.get(sessionName);
        
        if (!device) {
            return res.status(404).json({
                success: false,
                error: 'Session not found',
            });
        }

        logger.info({ sessionName, backupIndex }, 'Manual restore triggered');

        // Disconnect device first
        await device.disconnect();

        // Restore from backup
        const success = await SessionBackup.restoreSession(sessionName, backupIndex);

        if (success) {
            // Reconnect with restored session
            await device.connect();

            res.json({
                success: true,
                message: 'Session restored and reconnecting',
                sessionName,
            });
        } else {
            res.status(500).json({
                success: false,
                error: 'Restore failed',
            });
        }
    } catch (error) {
        logger.error({ error: error.message }, 'Failed to restore');
        res.status(500).json({
            success: false,
            error: error.message,
        });
    }
});

// POST /monitoring/queue/:sessionName/clear
// Clear message queue for a session
router.post('/queue/:sessionName/clear', (req, res) => {
    try {
        const { sessionName } = req.params;
        
        const device = SessionManager.get(sessionName);
        
        if (!device) {
            return res.status(404).json({
                success: false,
                error: 'Session not found',
            });
        }

        if (!device._messageQueue) {
            return res.json({
                success: true,
                cleared: 0,
            });
        }

        const cleared = device._messageQueue.clearQueue();

        res.json({
            success: true,
            cleared,
            sessionName,
        });
    } catch (error) {
        logger.error({ error: error.message }, 'Failed to clear queue');
        res.status(500).json({
            success: false,
            error: error.message,
        });
    }
});

// POST /monitoring/alerts/:alertId/resolve
// Resolve a specific alert
router.post('/alerts/:alertId/resolve', (req, res) => {
    try {
        const { alertId } = req.params;
        
        const resolved = AlertManager.resolveAlert(alertId);

        if (resolved) {
            res.json({
                success: true,
                message: 'Alert resolved',
                alertId,
            });
        } else {
            res.status(404).json({
                success: false,
                error: 'Alert not found',
            });
        }
    } catch (error) {
        logger.error({ error: error.message }, 'Failed to resolve alert');
        res.status(500).json({
            success: false,
            error: error.message,
        });
    }
});

// GET /monitoring/overview
// Return complete overview (dashboard summary)
router.get('/overview', (req, res) => {
    try {
        const devices = SessionManager.getAllDevices();
        const allStats = ConnectionStateManager.getAllStatistics();
        const alerts = AlertManager.getActiveAlerts();
        const connectionAlerts = ConnectionStateManager.getActiveAlerts();
        const queueStats = MessageQueue.getAllQueueStats();
        const backupStats = SessionBackup.getBackupStats();
        const healthReport = HealthMonitor.getHealthReport();

        // Calculate summary
        const totalDevices = devices.length;
        const connectedDevices = devices.filter(d => d.getStatus() === 'connected').length;
        const disconnectedDevices = devices.filter(d => d.getStatus() === 'disconnected').length;
        const reconnectingDevices = devices.filter(d => d.getStatus() === 'reconnecting').length;

        const totalAlerts = alerts.length + connectionAlerts.length;
        const totalQueuedMessages = queueStats.reduce((sum, q) => sum + q.pending, 0);

        const avgUptime = allStats.length > 0
            ? allStats.reduce((sum, s) => sum + s.uptimePercentage, 0) / allStats.length
            : 0;

        res.json({
            success: true,
            overview: {
                devices: {
                    total: totalDevices,
                    connected: connectedDevices,
                    disconnected: disconnectedDevices,
                    reconnecting: reconnectingDevices,
                },
                alerts: {
                    total: totalAlerts,
                    active: [...alerts, ...connectionAlerts],
                },
                queue: {
                    totalPending: totalQueuedMessages,
                    sessions: queueStats,
                },
                uptime: {
                    average: parseFloat(avgUptime.toFixed(2)),
                    statistics: allStats,
                },
                backups: backupStats,
                health: healthReport,
            },
            timestamp: new Date().toISOString(),
        });
    } catch (error) {
        logger.error({ error: error.message }, 'Failed to get overview');
        res.status(500).json({
            success: false,
            error: error.message,
        });
    }
});

module.exports = router;
