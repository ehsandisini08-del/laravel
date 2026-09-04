const { createLogger } = require('../utils/logger');
const { AlertManager } = require('../utils/alerts');

const logger = createLogger();

class HealthMonitor {
    static interval = null;
    static sessionManager = null;
    static isRunning = false;

    static startMonitoring(sessionManager) {
        if (this.isRunning) {
            logger.warn('Health monitoring already running');
            return;
        }

        this.sessionManager = sessionManager;
        this.isRunning = true;

        const checkInterval = parseInt(process.env.HEALTH_CHECK_INTERVAL || 30000);

        logger.info({ intervalMs: checkInterval }, 'Starting health monitoring');

        this.interval = setInterval(() => {
            this.performHealthCheck();
        }, checkInterval);

        // Perform immediate check
        this.performHealthCheck();
    }

    static stopMonitoring() {
        if (this.interval) {
            clearInterval(this.interval);
            this.interval = null;
        }
        this.isRunning = false;
        logger.info('Health monitoring stopped');
    }

    static async performHealthCheck() {
        if (!this.sessionManager) {
            return;
        }

        const devices = this.sessionManager.getAllDevices();

        logger.debug({ deviceCount: devices.length }, 'Performing health check');

        for (const device of devices) {
            await this.checkDevice(device);
        }
    }

    static async checkDevice(device) {
        const sessionName = device.sessionName;
        const status = device.getStatus();

        // Skip if not connected
        if (status !== 'connected') {
            logger.debug({ sessionName, status }, 'Skipping health check (not connected)');
            return;
        }

        // Check last activity time
        const timeSinceLastActivity = Date.now() - device._lastActivityTime;
        const activityTimeout = parseInt(process.env.HEALTH_CHECK_TIMEOUT || 300000);

        logger.debug({
            sessionName,
            timeSinceLastActivity,
            activityTimeout,
        }, 'Checking device activity');

        // If no activity for configured timeout, test connection
        if (timeSinceLastActivity > activityTimeout) {
            logger.info({ sessionName, timeSinceLastActivity }, 'No activity detected, testing connection');

            const isHealthy = await this.testConnection(device);

            if (!isHealthy) {
                await this.handleUnhealthy(device);
            }
        }
    }

    static async testConnection(device) {
        const sessionName = device.sessionName;

        try {
            if (!device.sock || !device.sock.user) {
                logger.warn({ sessionName }, 'Socket or user not available');
                return false;
            }

            // Test connection by fetching own status
            await device.sock.fetchStatus(device.sock.user.id);

            // Connection test passed
            device._isHealthy = true;
            device._lastActivityTime = Date.now();

            logger.debug({ sessionName }, 'Health check passed');

            return true;
        } catch (error) {
            logger.warn({
                sessionName,
                error: error.message,
            }, 'Health check failed');

            device._isHealthy = false;

            // Create alert
            AlertManager.createAlert(
                AlertManager.TYPES.HEALTH_CHECK_FAILED,
                sessionName,
                `Health check failed: ${error.message}`,
                'medium',
                { error: error.message }
            );

            return false;
        }
    }

    static async handleUnhealthy(device) {
        const sessionName = device.sessionName;

        logger.warn({ sessionName }, 'Device unhealthy, forcing reconnect');

        // Create alert
        AlertManager.createAlert(
            AlertManager.TYPES.HEALTH_CHECK_FAILED,
            sessionName,
            'Device unhealthy, forcing reconnect',
            'high'
        );

        try {
            // Force reconnect
            await device.forceReconnect();
        } catch (error) {
            logger.error({
                sessionName,
                error: error.message,
            }, 'Failed to force reconnect');
        }
    }

    static getHealthReport() {
        if (!this.sessionManager) {
            return {
                monitoring: false,
                devices: [],
            };
        }

        const devices = this.sessionManager.getAllDevices();
        const report = {
            monitoring: this.isRunning,
            checkInterval: parseInt(process.env.HEALTH_CHECK_INTERVAL || 30000),
            activityTimeout: parseInt(process.env.HEALTH_CHECK_TIMEOUT || 300000),
            devices: devices.map(device => ({
                sessionName: device.sessionName,
                status: device.getStatus(),
                isHealthy: device._isHealthy,
                lastActivity: new Date(device._lastActivityTime).toISOString(),
                timeSinceActivity: Date.now() - device._lastActivityTime,
            })),
        };

        return report;
    }
}

module.exports = { HealthMonitor };
