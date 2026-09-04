const { createLogger } = require('../utils/logger');

const logger = createLogger();

class ConnectionStateManager {
    static sessions = new Map();

    static recordConnect(sessionName) {
        const now = Date.now();
        
        if (!this.sessions.has(sessionName)) {
            this.sessions.set(sessionName, {
                sessionName,
                status: 'connected',
                connectTime: now,
                disconnectTime: null,
                currentUptime: 0,
                totalUptime: 0,
                totalDowntime: 0,
                totalConnects: 0,
                totalDisconnects: 0,
                lastConnect: null,
                lastDisconnect: null,
                reconnectTimes: [],
                history: [],
            });
        }

        const state = this.sessions.get(sessionName);
        state.status = 'connected';
        state.connectTime = now;
        state.lastConnect = new Date(now).toISOString();
        state.totalConnects++;

        // Calculate reconnect time if there was a previous disconnect
        if (state.disconnectTime) {
            const reconnectTime = now - state.disconnectTime;
            state.reconnectTimes.push(reconnectTime);
            // Keep only last 100 reconnect times
            if (state.reconnectTimes.length > 100) {
                state.reconnectTimes.shift();
            }
        }

        // Add to history
        state.history.unshift({
            event: 'connect',
            timestamp: new Date(now).toISOString(),
            details: {
                reconnectTime: state.disconnectTime ? now - state.disconnectTime : 0,
            },
        });

        // Keep only last 100 history items
        if (state.history.length > 100) {
            state.history.pop();
        }

        logger.info({ sessionName, totalConnects: state.totalConnects }, 'Connection recorded');
    }

    static recordDisconnect(sessionName, reason = 'unknown') {
        const now = Date.now();

        if (!this.sessions.has(sessionName)) {
            this.sessions.set(sessionName, {
                sessionName,
                status: 'disconnected',
                connectTime: null,
                disconnectTime: now,
                currentUptime: 0,
                totalUptime: 0,
                totalDowntime: 0,
                totalConnects: 0,
                totalDisconnects: 0,
                lastConnect: null,
                lastDisconnect: null,
                reconnectTimes: [],
                history: [],
            });
        }

        const state = this.sessions.get(sessionName);
        
        // Calculate uptime from this connection
        if (state.connectTime) {
            const uptime = now - state.connectTime;
            state.totalUptime += uptime;
        }

        state.status = 'disconnected';
        state.disconnectTime = now;
        state.lastDisconnect = new Date(now).toISOString();
        state.totalDisconnects++;

        // Add to history
        state.history.unshift({
            event: 'disconnect',
            timestamp: new Date(now).toISOString(),
            reason,
            details: {
                uptimeBeforeDisconnect: state.connectTime ? now - state.connectTime : 0,
            },
        });

        // Keep only last 100 history items
        if (state.history.length > 100) {
            state.history.pop();
        }

        logger.info({ sessionName, reason, totalDisconnects: state.totalDisconnects }, 'Disconnection recorded');
    }

    static getStatistics(sessionName) {
        if (!this.sessions.has(sessionName)) {
            return null;
        }

        const state = this.sessions.get(sessionName);
        const now = Date.now();

        // Calculate current uptime if connected
        let currentUptime = 0;
        if (state.status === 'connected' && state.connectTime) {
            currentUptime = now - state.connectTime;
        }

        // Calculate current disconnect duration if disconnected
        let currentDisconnectDuration = 0;
        if (state.status === 'disconnected' && state.disconnectTime) {
            currentDisconnectDuration = now - state.disconnectTime;
        }

        // Calculate total time
        const totalTime = state.totalUptime + state.totalDowntime + currentUptime + currentDisconnectDuration;

        // Calculate uptime percentage
        const uptimePercentage = totalTime > 0 
            ? ((state.totalUptime + currentUptime) / totalTime * 100).toFixed(2)
            : 0;

        // Calculate average reconnect time
        const avgReconnectTime = state.reconnectTimes.length > 0
            ? Math.round(state.reconnectTimes.reduce((a, b) => a + b, 0) / state.reconnectTimes.length)
            : 0;

        // Find longest uptime
        let longestUptime = 0;
        for (let i = 0; i < state.history.length; i++) {
            if (state.history[i].event === 'disconnect' && state.history[i].details.uptimeBeforeDisconnect) {
                longestUptime = Math.max(longestUptime, state.history[i].details.uptimeBeforeDisconnect);
            }
        }

        return {
            sessionName: state.sessionName,
            status: state.status,
            currentUptime,
            currentDisconnectDuration,
            totalUptime: state.totalUptime,
            totalDowntime: state.totalDowntime,
            uptimePercentage: parseFloat(uptimePercentage),
            totalConnects: state.totalConnects,
            totalDisconnects: state.totalDisconnects,
            lastConnect: state.lastConnect,
            lastDisconnect: state.lastDisconnect,
            avgReconnectTime,
            longestUptime,
        };
    }

    static getConnectionHistory(sessionName, limit = 100) {
        if (!this.sessions.has(sessionName)) {
            return [];
        }

        const state = this.sessions.get(sessionName);
        return state.history.slice(0, limit);
    }

    static getUptimePercentage(sessionName) {
        const stats = this.getStatistics(sessionName);
        return stats ? stats.uptimePercentage : 0;
    }

    static shouldAlert(sessionName) {
        if (!this.sessions.has(sessionName)) {
            return false;
        }

        const state = this.sessions.get(sessionName);
        const now = Date.now();

        // Alert if disconnected for more than 5 minutes
        const alertThreshold = parseInt(process.env.ALERT_DISCONNECT_THRESHOLD || 300000);
        
        if (state.status === 'disconnected' && state.disconnectTime) {
            const disconnectDuration = now - state.disconnectTime;
            return disconnectDuration > alertThreshold;
        }

        return false;
    }

    static getActiveAlerts() {
        const alerts = [];

        for (const [sessionName, state] of this.sessions.entries()) {
            if (this.shouldAlert(sessionName)) {
                const now = Date.now();
                const disconnectDuration = now - state.disconnectTime;
                const minutes = Math.floor(disconnectDuration / 60000);

                alerts.push({
                    type: 'extended_disconnect',
                    sessionName,
                    message: `Device disconnected for ${minutes} minutes`,
                    severity: 'high',
                    timestamp: new Date().toISOString(),
                    duration: disconnectDuration,
                });
            }

            // Alert for unstable connection (> 10 disconnects in last 20 events)
            const recentHistory = state.history.slice(0, 20);
            const recentDisconnects = recentHistory.filter(h => h.event === 'disconnect').length;
            
            if (recentDisconnects > 10) {
                alerts.push({
                    type: 'unstable_connection',
                    sessionName,
                    message: `Unstable connection detected (${recentDisconnects} disconnects in last 20 events)`,
                    severity: 'medium',
                    timestamp: new Date().toISOString(),
                });
            }
        }

        return alerts;
    }

    static getAllStatistics() {
        const stats = [];
        for (const sessionName of this.sessions.keys()) {
            stats.push(this.getStatistics(sessionName));
        }
        return stats;
    }

    static reset(sessionName) {
        this.sessions.delete(sessionName);
        logger.info({ sessionName }, 'Connection state reset');
    }
}

module.exports = { ConnectionStateManager };
