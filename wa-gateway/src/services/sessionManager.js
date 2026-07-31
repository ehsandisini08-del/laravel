const { createLogger } = require('../utils/logger');
const { BaileysDevice } = require('./baileysDevice');

const logger = createLogger();

class SessionManager {
    static sessions = new Map();

    static getOrCreate(sessionName) {
        if (this.sessions.has(sessionName)) {
            return this.sessions.get(sessionName);
        }

        const device = new BaileysDevice(sessionName);
        this.sessions.set(sessionName, device);
        return device;
    }

    static get(sessionName) {
        return this.sessions.get(sessionName) || null;
    }

    static has(sessionName) {
        return this.sessions.has(sessionName);
    }

    static async remove(sessionName) {
        const device = this.sessions.get(sessionName);
        if (device) {
            await device.cleanup();
            this.sessions.delete(sessionName);
        }
    }

    static async restoreAll() {
        const fs = require('fs');
        const path = require('path');
        const sessionDir = path.resolve(process.env.SESSION_DIR || './sessions');

        if (!fs.existsSync(sessionDir)) {
            return;
        }

        const dirs = fs.readdirSync(sessionDir, { withFileTypes: true })
            .filter(d => d.isDirectory())
            .map(d => d.name);

        for (const sessionName of dirs) {
            try {
                const device = this.getOrCreate(sessionName);
                await device.restore();
                logger.info({ sessionName }, 'Session restored');
            } catch (error) {
                logger.error({ sessionName, error: error.message }, 'Failed to restore session');
            }
        }
    }

    static async cleanup() {
        for (const [name, device] of this.sessions) {
            await device.cleanup();
        }
        this.sessions.clear();
    }

    static getAllStatus() {
        const statuses = [];
        for (const [name, device] of this.sessions) {
            statuses.push({
                session: name,
                status: device.getStatus(),
                phoneNumber: device.phoneNumber,
                profileName: device.profileName,
                lastSeen: device.lastSeen,
            });
        }
        return statuses;
    }
}

module.exports = { SessionManager };