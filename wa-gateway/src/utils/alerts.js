const { createLogger } = require('../utils/logger');

const logger = createLogger();

class AlertManager {
    static alerts = [];
    static maxAlerts = 100;

    static createAlert(type, sessionName, message, severity = 'medium', details = {}) {
        const alert = {
            id: `alert_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
            type,
            sessionName,
            message,
            severity, // 'low', 'medium', 'high', 'critical'
            timestamp: new Date().toISOString(),
            resolved: false,
            details,
        };

        // Check for duplicate alerts (same type + session + not resolved)
        const existingAlert = this.alerts.find(
            a => a.type === type && 
                 a.sessionName === sessionName && 
                 !a.resolved
        );

        if (existingAlert) {
            // Update existing alert instead of creating duplicate
            existingAlert.message = message;
            existingAlert.timestamp = new Date().toISOString();
            existingAlert.details = { ...existingAlert.details, ...details };
            logger.debug({ alertId: existingAlert.id, type, sessionName }, 'Alert updated');
            return existingAlert;
        }

        // Add new alert
        this.alerts.unshift(alert);

        // Keep only max alerts
        if (this.alerts.length > this.maxAlerts) {
            this.alerts = this.alerts.slice(0, this.maxAlerts);
        }

        logger.info({ alertId: alert.id, type, sessionName, severity }, 'Alert created');

        return alert;
    }

    static getActiveAlerts() {
        return this.alerts.filter(a => !a.resolved);
    }

    static getAllAlerts(limit = 50) {
        return this.alerts.slice(0, limit);
    }

    static getAlertsBySession(sessionName) {
        return this.alerts.filter(a => a.sessionName === sessionName && !a.resolved);
    }

    static resolveAlert(alertId) {
        const alert = this.alerts.find(a => a.id === alertId);
        if (alert) {
            alert.resolved = true;
            alert.resolvedAt = new Date().toISOString();
            logger.info({ alertId, type: alert.type }, 'Alert resolved');
            return true;
        }
        return false;
    }

    static resolveAlertsByType(type, sessionName) {
        let resolved = 0;
        for (const alert of this.alerts) {
            if (alert.type === type && alert.sessionName === sessionName && !alert.resolved) {
                alert.resolved = true;
                alert.resolvedAt = new Date().toISOString();
                resolved++;
            }
        }
        
        if (resolved > 0) {
            logger.info({ type, sessionName, count: resolved }, 'Alerts resolved by type');
        }
        
        return resolved;
    }

    static clearResolvedAlerts() {
        const beforeCount = this.alerts.length;
        this.alerts = this.alerts.filter(a => !a.resolved);
        const cleared = beforeCount - this.alerts.length;
        
        if (cleared > 0) {
            logger.info({ cleared }, 'Resolved alerts cleared');
        }
        
        return cleared;
    }

    static clearAll() {
        const count = this.alerts.length;
        this.alerts = [];
        logger.info({ count }, 'All alerts cleared');
        return count;
    }

    static getAlertStats() {
        const total = this.alerts.length;
        const active = this.alerts.filter(a => !a.resolved).length;
        const resolved = total - active;

        const bySeverity = {
            critical: 0,
            high: 0,
            medium: 0,
            low: 0,
        };

        for (const alert of this.alerts) {
            if (!alert.resolved && bySeverity[alert.severity] !== undefined) {
                bySeverity[alert.severity]++;
            }
        }

        return {
            total,
            active,
            resolved,
            bySeverity,
        };
    }
}

// Alert Types Constants
AlertManager.TYPES = {
    EXTENDED_DISCONNECT: 'extended_disconnect',
    UNSTABLE_CONNECTION: 'unstable_connection',
    SESSION_RESET: 'session_reset',
    SESSION_RESTORED: 'session_restored',
    HEALTH_CHECK_FAILED: 'health_check_failed',
    MEMORY_HIGH: 'memory_high',
    QUEUE_GROWING: 'queue_growing',
    BACKUP_FAILED: 'backup_failed',
    RESTORE_FAILED: 'restore_failed',
};

module.exports = { AlertManager };
