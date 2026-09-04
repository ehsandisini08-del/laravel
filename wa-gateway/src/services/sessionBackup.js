const path = require('path');
const fs = require('fs');
const { createLogger } = require('../utils/logger');
const { AlertManager } = require('../utils/alerts');

const logger = createLogger();

class SessionBackup {
    static backupInterval = null;
    static isRunning = false;

    static startGlobalBackupScheduler() {
        if (this.isRunning) {
            logger.warn('Backup scheduler already running');
            return;
        }

        const backupEnabled = process.env.BACKUP_ENABLED !== 'false';
        if (!backupEnabled) {
            logger.info('Session backup disabled via config');
            return;
        }

        const interval = parseInt(process.env.BACKUP_INTERVAL || 300000);

        logger.info({ intervalMs: interval }, 'Starting session backup scheduler');

        this.isRunning = true;

        this.backupInterval = setInterval(() => {
            this.backupAllSessions();
        }, interval);
    }

    static stopScheduler() {
        if (this.backupInterval) {
            clearInterval(this.backupInterval);
            this.backupInterval = null;
        }
        this.isRunning = false;
        logger.info('Backup scheduler stopped');
    }

    static async backupAllSessions() {
        const sessionDir = path.resolve(process.env.SESSION_DIR || './sessions');

        if (!fs.existsSync(sessionDir)) {
            return;
        }

        const dirs = fs.readdirSync(sessionDir, { withFileTypes: true })
            .filter(d => d.isDirectory() && d.name !== 'backups')
            .map(d => d.name);

        logger.debug({ sessionCount: dirs.length }, 'Backing up all sessions');

        for (const sessionName of dirs) {
            await this.backupSession(sessionName);
        }
    }

    static async backupSession(sessionName) {
        try {
            const sessionDir = path.resolve(process.env.SESSION_DIR || './sessions', sessionName);
            
            // Check if session exists and has files
            if (!fs.existsSync(sessionDir)) {
                logger.debug({ sessionName }, 'Session directory not found, skipping backup');
                return false;
            }

            const files = fs.readdirSync(sessionDir);
            if (files.length === 0) {
                logger.debug({ sessionName }, 'Session directory empty, skipping backup');
                return false;
            }

            // Create backup directory structure
            const backupBaseDir = path.resolve(process.env.SESSION_DIR || './sessions', 'backups', sessionName);
            if (!fs.existsSync(backupBaseDir)) {
                fs.mkdirSync(backupBaseDir, { recursive: true });
            }

            // Create timestamp for this backup
            const timestamp = new Date().toISOString().replace(/:/g, '-').replace(/\..+/, '');
            const backupDir = path.join(backupBaseDir, timestamp);

            // Copy session files to backup
            await this.copyDirectory(sessionDir, backupDir);

            logger.info({ sessionName, backupDir }, 'Session backed up successfully');

            // Cleanup old backups
            await this.cleanupOldBackups(sessionName);

            return true;
        } catch (error) {
            logger.error({
                sessionName,
                error: error.message,
            }, 'Failed to backup session');

            AlertManager.createAlert(
                AlertManager.TYPES.BACKUP_FAILED,
                sessionName,
                `Backup failed: ${error.message}`,
                'low',
                { error: error.message }
            );

            return false;
        }
    }

    static async restoreSession(sessionName, backupIndex = 0) {
        try {
            const backupBaseDir = path.resolve(process.env.SESSION_DIR || './sessions', 'backups', sessionName);

            if (!fs.existsSync(backupBaseDir)) {
                logger.warn({ sessionName }, 'No backups found for session');
                return false;
            }

            // Get list of backups (sorted newest first)
            const backups = fs.readdirSync(backupBaseDir)
                .filter(name => fs.statSync(path.join(backupBaseDir, name)).isDirectory())
                .sort()
                .reverse();

            if (backups.length === 0) {
                logger.warn({ sessionName }, 'No backup directories found');
                return false;
            }

            // Use specified backup index (0 = newest)
            if (backupIndex >= backups.length) {
                logger.warn({ sessionName, backupIndex, available: backups.length }, 'Backup index out of range');
                return false;
            }

            const backupToRestore = backups[backupIndex];
            const backupPath = path.join(backupBaseDir, backupToRestore);
            const sessionDir = path.resolve(process.env.SESSION_DIR || './sessions', sessionName);

            logger.info({
                sessionName,
                backup: backupToRestore,
                index: backupIndex,
            }, 'Restoring session from backup');

            // Remove current session directory
            if (fs.existsSync(sessionDir)) {
                fs.rmSync(sessionDir, { recursive: true, force: true });
            }

            // Create fresh directory
            fs.mkdirSync(sessionDir, { recursive: true });

            // Copy backup files to session directory
            await this.copyDirectory(backupPath, sessionDir);

            logger.info({ sessionName, backup: backupToRestore }, 'Session restored successfully');

            AlertManager.createAlert(
                AlertManager.TYPES.SESSION_RESTORED,
                sessionName,
                `Session restored from backup: ${backupToRestore}`,
                'medium',
                { backup: backupToRestore, index: backupIndex }
            );

            return true;
        } catch (error) {
            logger.error({
                sessionName,
                error: error.message,
            }, 'Failed to restore session');

            AlertManager.createAlert(
                AlertManager.TYPES.RESTORE_FAILED,
                sessionName,
                `Restore failed: ${error.message}`,
                'high',
                { error: error.message }
            );

            return false;
        }
    }

    static async cleanupOldBackups(sessionName) {
        try {
            const backupBaseDir = path.resolve(process.env.SESSION_DIR || './sessions', 'backups', sessionName);

            if (!fs.existsSync(backupBaseDir)) {
                return;
            }

            const retention = parseInt(process.env.BACKUP_RETENTION || 3);

            // Get list of backups (sorted newest first)
            const backups = fs.readdirSync(backupBaseDir)
                .filter(name => fs.statSync(path.join(backupBaseDir, name)).isDirectory())
                .sort()
                .reverse();

            // Remove old backups beyond retention count
            const backupsToDelete = backups.slice(retention);

            if (backupsToDelete.length > 0) {
                logger.info({
                    sessionName,
                    deleting: backupsToDelete.length,
                    retention,
                }, 'Cleaning up old backups');

                for (const backup of backupsToDelete) {
                    const backupPath = path.join(backupBaseDir, backup);
                    fs.rmSync(backupPath, { recursive: true, force: true });
                }
            }
        } catch (error) {
            logger.error({
                sessionName,
                error: error.message,
            }, 'Failed to cleanup old backups');
        }
    }

    static listBackups(sessionName) {
        try {
            const backupBaseDir = path.resolve(process.env.SESSION_DIR || './sessions', 'backups', sessionName);

            if (!fs.existsSync(backupBaseDir)) {
                return [];
            }

            // Get list of backups with metadata
            const backups = fs.readdirSync(backupBaseDir)
                .filter(name => {
                    const backupPath = path.join(backupBaseDir, name);
                    return fs.statSync(backupPath).isDirectory();
                })
                .map(name => {
                    const backupPath = path.join(backupBaseDir, name);
                    const stats = fs.statSync(backupPath);
                    const files = fs.readdirSync(backupPath);

                    return {
                        name,
                        timestamp: name,
                        createdAt: stats.birthtime.toISOString(),
                        size: this.getDirectorySize(backupPath),
                        fileCount: files.length,
                    };
                })
                .sort((a, b) => b.timestamp.localeCompare(a.timestamp));

            return backups;
        } catch (error) {
            logger.error({
                sessionName,
                error: error.message,
            }, 'Failed to list backups');
            return [];
        }
    }

    static async copyDirectory(source, destination) {
        // Create destination if not exists
        if (!fs.existsSync(destination)) {
            fs.mkdirSync(destination, { recursive: true });
        }

        // Get all files and subdirectories
        const entries = fs.readdirSync(source, { withFileTypes: true });

        for (const entry of entries) {
            const sourcePath = path.join(source, entry.name);
            const destPath = path.join(destination, entry.name);

            if (entry.isDirectory()) {
                // Recursively copy subdirectory
                await this.copyDirectory(sourcePath, destPath);
            } else {
                // Copy file
                fs.copyFileSync(sourcePath, destPath);
            }
        }
    }

    static getDirectorySize(dirPath) {
        let size = 0;

        const entries = fs.readdirSync(dirPath, { withFileTypes: true });

        for (const entry of entries) {
            const entryPath = path.join(dirPath, entry.name);

            if (entry.isDirectory()) {
                size += this.getDirectorySize(entryPath);
            } else {
                const stats = fs.statSync(entryPath);
                size += stats.size;
            }
        }

        return size;
    }

    static getBackupStats() {
        try {
            const backupBaseDir = path.resolve(process.env.SESSION_DIR || './sessions', 'backups');

            if (!fs.existsSync(backupBaseDir)) {
                return {
                    totalBackups: 0,
                    totalSize: 0,
                    sessions: [],
                };
            }

            const sessionDirs = fs.readdirSync(backupBaseDir, { withFileTypes: true })
                .filter(d => d.isDirectory())
                .map(d => d.name);

            let totalBackups = 0;
            let totalSize = 0;
            const sessions = [];

            for (const sessionName of sessionDirs) {
                const backups = this.listBackups(sessionName);
                const sessionSize = backups.reduce((sum, b) => sum + b.size, 0);

                totalBackups += backups.length;
                totalSize += sessionSize;

                sessions.push({
                    sessionName,
                    backupCount: backups.length,
                    totalSize: sessionSize,
                    latestBackup: backups[0]?.createdAt || null,
                });
            }

            return {
                totalBackups,
                totalSize,
                sessions,
            };
        } catch (error) {
            logger.error({ error: error.message }, 'Failed to get backup stats');
            return {
                totalBackups: 0,
                totalSize: 0,
                sessions: [],
            };
        }
    }
}

module.exports = { SessionBackup };
