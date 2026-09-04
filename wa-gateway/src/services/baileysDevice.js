const path = require('path');
const fs = require('fs');
const { makeWASocket, useMultiFileAuthState, DisconnectReason, fetchLatestBaileysVersion, makeCacheableSignalKeyStore } = require('@whiskeysockets/baileys');
const QRCode = require('qrcode');
const { createLogger } = require('../utils/logger');
const { sendWebhook } = require('./webhook');

const logger = createLogger();

let cachedBaileysVersion = null;

class BaileysDevice {
    constructor(sessionName) {
        this.sessionName = sessionName;
        this.sock = null;
        this.qrCode = null;
        this.status = 'disconnected';
        this.phoneNumber = null;
        this.profileName = null;
        this.lastSeen = null;
        this.sessionDir = path.resolve(process.env.SESSION_DIR || './sessions', sessionName);
        this._reconnectAttempts = 0;
        this._reconnectTimer = null;
        this._manualClose = false;
        
        // NEW: Health monitoring
        this._healthCheckInterval = null;
        this._lastActivityTime = Date.now();
        this._isHealthy = true;
        
        // NEW: Backup
        this._backupInterval = null;
        
        // NEW: Message queue
        this._messageQueue = null;
        
        // NEW: Connection tracking
        this._connectTime = null;
        this._disconnectTime = null;
    }

    static shouldReconnect(statusCode) {
        return ![
            DisconnectReason.loggedOut,
            DisconnectReason.badSession,
            DisconnectReason.multideviceMismatch,
        ].includes(statusCode);
    }

    getStatus() {
        return this.status;
    }

    async restore() {
        try {
            await this.connect();
        } catch (error) {
            logger.error({ sessionName: this.sessionName, error: error.message }, 'Restore failed');
        }
    }

    async connect() {
        if (this.sock) {
            return;
        }

        this._manualClose = false;

        if (!fs.existsSync(this.sessionDir)) {
            fs.mkdirSync(this.sessionDir, { recursive: true });
        }

        const { state, saveCreds } = await useMultiFileAuthState(this.sessionDir);

        if (!cachedBaileysVersion) {
            const fetched = await fetchLatestBaileysVersion();
            cachedBaileysVersion = fetched.version;
        }

        this.status = 'connecting';
        this.qrCode = null;

        const sock = makeWASocket({
            version: cachedBaileysVersion,
            auth: {
                creds: state.creds,
                keys: makeCacheableSignalKeyStore(state.keys, logger),
            },
            printQRInTerminal: false,
            browser: ['WA Gateway', 'Chrome', '1.0.0'],
        });

        this.sock = sock;

        sock.ev.on('creds.update', saveCreds);

        sock.ev.on('connection.update', async (update) => {
            const { connection, lastDisconnect, qr } = update;

            if (qr) {
                this.status = 'qr_waiting';
                this.qrCode = await QRCode.toDataURL(qr);
                this._reconnectAttempts = 0;
                await sendWebhook('qr_updated', this.sessionName, {
                    qr_code: this.qrCode,
                });
                logger.info({ sessionName: this.sessionName }, 'QR code generated');
            }

            if (connection === 'open') {
                this.status = 'connected';
                this.qrCode = null;
                this._reconnectAttempts = 0;
                this.lastSeen = new Date().toISOString();
                this._connectTime = Date.now();

                const user = sock.user;
                if (user) {
                    this.phoneNumber = user.id?.split(':')[0]?.replace('@s.whatsapp.net', '') || null;
                    this.profileName = user.name || null;
                }

                // NEW: Start health monitoring & backup
                this.startHealthCheck();
                this.startBackupSchedule();

                // NEW: Record connect event
                const { ConnectionStateManager } = require('./connectionStateManager');
                ConnectionStateManager.recordConnect(this.sessionName);

                // NEW: Process queued messages
                if (this._messageQueue) {
                    await this._messageQueue.processQueue();
                }

                await sendWebhook('connected', this.sessionName, {
                    phone_number: this.phoneNumber,
                    profile_name: this.profileName,
                });

                logger.info({ sessionName: this.sessionName, phone: this.phoneNumber }, 'Connected');
            }

            if (connection === 'close') {
                const statusCode = lastDisconnect?.error?.output?.statusCode;
                this.sock = null;
                this._disconnectTime = Date.now();

                // NEW: Stop health check & backup
                this.stopHealthCheck();
                this.stopBackupSchedule();

                // NEW: Record disconnect event
                const { ConnectionStateManager } = require('./connectionStateManager');
                const reason = this.getDisconnectReason(statusCode);
                ConnectionStateManager.recordDisconnect(this.sessionName, reason);

                if (statusCode === DisconnectReason.loggedOut) {
                    this.status = 'logged_out';
                    this.qrCode = null;
                    await sendWebhook('disconnected', this.sessionName, {
                        reason: 'logged_out',
                        will_reconnect: false,
                    });
                    logger.info({ sessionName: this.sessionName }, 'Logged out');
                    return;
                }

                if (statusCode === DisconnectReason.badSession || statusCode === DisconnectReason.multideviceMismatch) {
                    logger.warn({ sessionName: this.sessionName, statusCode }, 'Session invalid, trying restore from backup...');

                    // NEW: Try restore from backup
                    const { SessionBackup } = require('./sessionBackup');
                    const restored = await SessionBackup.restoreSession(this.sessionName);

                    if (!restored) {
                        // Backup restore failed, reset for new QR
                        this.resetSession();
                    }

                    this.status = 'disconnected';
                    this.qrCode = null;
                    await sendWebhook('disconnected', this.sessionName, {
                        reason: 'session_reset',
                        will_reconnect: true,
                        backup_restored: restored,
                    });
                    this.scheduleReconnect(1);
                    return;
                }

                // Connection lost - UNLIMITED RECONNECT with smart backoff
                this._reconnectAttempts++;
                this.status = 'reconnecting';
                await sendWebhook('disconnected', this.sessionName, {
                    reason: 'connection_lost',
                    will_reconnect: true,
                    attempt: this._reconnectAttempts,
                });
                logger.info({
                    sessionName: this.sessionName,
                    attempt: this._reconnectAttempts,
                    statusCode,
                }, 'Reconnecting...');

                this.scheduleReconnect(this._reconnectAttempts);
            }
        });

        sock.ev.on('messages.upsert', async (m) => {
            this._lastActivityTime = Date.now(); // Update activity timestamp

            for (const msg of m.messages) {
                if (!msg.key.fromMe) {
                    await sendWebhook('message_received', this.sessionName, {
                        message: msg,
                    });
                }
            }
        });

        sock.ev.on('error', (err) => {
            logger.error({ sessionName: this.sessionName, error: err.message }, 'Socket error');
        });
    }

    scheduleReconnect(attempt) {
        if (this._manualClose) {
            return;
        }

        // UNLIMITED RECONNECT with progressive backoff
        // 3s -> 10s -> 30s -> 60s (then stay at 60s forever)
        const delays = [3000, 10000, 30000, 60000];
        const delayIndex = Math.min(attempt - 1, delays.length - 1);
        const delay = delays[delayIndex];

        logger.info({
            sessionName: this.sessionName,
            attempt,
            nextRetryIn: `${delay}ms`,
        }, 'Scheduling reconnect');

        clearTimeout(this._reconnectTimer);
        this._reconnectTimer = setTimeout(() => this.connect(), delay);

        // Auto-reset session if stuck (every 20 failed attempts)
        if (attempt > 0 && attempt % 20 === 0) {
            logger.warn({
                sessionName: this.sessionName,
                attempt,
            }, 'Too many reconnect attempts, trying restore from backup...');

            // Try restore from backup first
            const { SessionBackup } = require('./sessionBackup');
            const { AlertManager } = require('../utils/alerts');
            
            SessionBackup.restoreSession(this.sessionName).then(restored => {
                if (!restored) {
                    // No backup available, full reset
                    logger.warn({ sessionName: this.sessionName }, 'No backup available, resetting session');
                    this.resetSession();
                    
                    AlertManager.createAlert(
                        AlertManager.TYPES.SESSION_RESET,
                        this.sessionName,
                        'Session reset after 20 reconnect attempts (no backup available)',
                        'high'
                    );
                }
            });
        }
    }

    resetSession() {
        clearTimeout(this._reconnectTimer);
        this._reconnectTimer = null;
        if (fs.existsSync(this.sessionDir)) {
            fs.rmSync(this.sessionDir, { recursive: true, force: true });
        }
        fs.mkdirSync(this.sessionDir, { recursive: true });
    }

    async disconnect() {
        this._manualClose = true;
        clearTimeout(this._reconnectTimer);
        this._reconnectTimer = null;
        
        this.stopHealthCheck();
        this.stopBackupSchedule();
        
        if (this.sock) {
            this.sock.end();
            this.sock = null;
        }
        this.status = 'disconnected';
        this.lastSeen = new Date().toISOString();
    }

    async logout() {
        this._manualClose = true;
        clearTimeout(this._reconnectTimer);
        this._reconnectTimer = null;
        
        this.stopHealthCheck();
        this.stopBackupSchedule();
        
        if (this.sock) {
            try {
                await this.sock.logout();
            } catch { /* ignore */ }
            this.sock = null;
        }

        if (fs.existsSync(this.sessionDir)) {
            fs.rmSync(this.sessionDir, { recursive: true, force: true });
        }

        this.status = 'logged_out';
        this.qrCode = null;
        this.phoneNumber = null;
        this.profileName = null;
    }

    async sendText(phone, text) {
        // Initialize queue if not exists
        if (!this._messageQueue) {
            this.initializeMessageQueue();
        }

        // If not connected, add to queue
        if (!this.sock || this.status !== 'connected') {
            await this._messageQueue.addToQueue({
                type: 'text',
                recipient: phone,
                content: text,
            });

            logger.info({
                sessionName: this.sessionName,
                phone,
            }, 'Message queued (device not connected)');

            return { queued: true, message: 'Message will be sent when device reconnects' };
        }

        // Send immediately if connected
        try {
            const jid = `${phone}@s.whatsapp.net`;
            const result = await this.sock.sendMessage(jid, { text });

            this._lastActivityTime = Date.now(); // Update activity

            await sendWebhook('message_sent', this.sessionName, {
                message_id: result.key.id,
                phone,
                status: 'sent',
            });

            return result;
        } catch (error) {
            // Send failed, add to queue for retry
            await this._messageQueue.addToQueue({
                type: 'text',
                recipient: phone,
                content: text,
            });

            throw error;
        }
    }

    async sendImage(phone, imageUrl, caption) {
        // Initialize queue if not exists
        if (!this._messageQueue) {
            this.initializeMessageQueue();
        }

        // If not connected, add to queue
        if (!this.sock || this.status !== 'connected') {
            await this._messageQueue.addToQueue({
                type: 'image',
                recipient: phone,
                content: { url: imageUrl, caption },
            });

            logger.info({
                sessionName: this.sessionName,
                phone,
            }, 'Image message queued (device not connected)');

            return { queued: true, message: 'Message will be sent when device reconnects' };
        }

        try {
            const jid = `${phone}@s.whatsapp.net`;
            const result = await this.sock.sendMessage(jid, {
                image: { url: imageUrl },
                caption: caption || '',
            });

            this._lastActivityTime = Date.now();

            return result;
        } catch (error) {
            // Send failed, add to queue for retry
            await this._messageQueue.addToQueue({
                type: 'image',
                recipient: phone,
                content: { url: imageUrl, caption },
            });

            throw error;
        }
    }

    async sendDocument(phone, documentUrl, fileName) {
        // Initialize queue if not exists
        if (!this._messageQueue) {
            this.initializeMessageQueue();
        }

        // If not connected, add to queue
        if (!this.sock || this.status !== 'connected') {
            await this._messageQueue.addToQueue({
                type: 'document',
                recipient: phone,
                content: { url: documentUrl, fileName },
            });

            logger.info({
                sessionName: this.sessionName,
                phone,
            }, 'Document message queued (device not connected)');

            return { queued: true, message: 'Message will be sent when device reconnects' };
        }

        try {
            const jid = `${phone}@s.whatsapp.net`;
            const result = await this.sock.sendMessage(jid, {
                document: { url: documentUrl },
                fileName: fileName || 'document',
                mimetype: 'application/octet-stream',
            });

            this._lastActivityTime = Date.now();

            return result;
        } catch (error) {
            // Send failed, add to queue for retry
            await this._messageQueue.addToQueue({
                type: 'document',
                recipient: phone,
                content: { url: documentUrl, fileName },
            });

            throw error;
        }
    }

    async cleanup() {
        this._manualClose = true;
        clearTimeout(this._reconnectTimer);
        this._reconnectTimer = null;
        
        this.stopHealthCheck();
        this.stopBackupSchedule();
        
        if (this.sock) {
            this.sock.end();
            this.sock = null;
        }
    }

    // NEW METHODS

    initializeMessageQueue() {
        const { MessageQueue } = require('./messageQueue');
        this._messageQueue = new MessageQueue(this.sessionName);
    }

    startHealthCheck() {
        if (this._healthCheckInterval) {
            clearInterval(this._healthCheckInterval);
        }

        const interval = parseInt(process.env.HEALTH_CHECK_INTERVAL || 30000);

        this._healthCheckInterval = setInterval(() => {
            this.performHealthCheck();
        }, interval);

        logger.debug({ sessionName: this.sessionName, interval }, 'Health check started');
    }

    stopHealthCheck() {
        if (this._healthCheckInterval) {
            clearInterval(this._healthCheckInterval);
            this._healthCheckInterval = null;
            logger.debug({ sessionName: this.sessionName }, 'Health check stopped');
        }
    }

    async performHealthCheck() {
        if (this.status !== 'connected' || !this.sock) {
            return;
        }

        const timeSinceLastActivity = Date.now() - this._lastActivityTime;
        const activityTimeout = parseInt(process.env.HEALTH_CHECK_TIMEOUT || 300000);

        // If no activity for configured timeout, test connection
        if (timeSinceLastActivity > activityTimeout) {
            logger.info({
                sessionName: this.sessionName,
                timeSinceLastActivity,
            }, 'No activity detected, testing connection');

            try {
                // Test connection by fetching own status
                await this.sock.fetchStatus(this.sock.user.id);
                this._isHealthy = true;
                this._lastActivityTime = Date.now();
                logger.debug({ sessionName: this.sessionName }, 'Health check passed');
            } catch (error) {
                this._isHealthy = false;
                logger.warn({
                    sessionName: this.sessionName,
                    error: error.message,
                }, 'Health check failed, forcing reconnect');

                const { AlertManager } = require('../utils/alerts');
                AlertManager.createAlert(
                    AlertManager.TYPES.HEALTH_CHECK_FAILED,
                    this.sessionName,
                    `Health check failed: ${error.message}`,
                    'medium',
                    { error: error.message }
                );

                // Force reconnect
                await this.forceReconnect();
            }
        }
    }

    async forceReconnect() {
        logger.info({ sessionName: this.sessionName }, 'Forcing reconnect...');

        if (this.sock) {
            this.sock.end();
            this.sock = null;
        }

        this.status = 'reconnecting';
        this._reconnectAttempts = 0;

        await this.connect();
    }

    startBackupSchedule() {
        if (this._backupInterval) {
            clearInterval(this._backupInterval);
        }

        const backupEnabled = process.env.BACKUP_ENABLED !== 'false';
        if (!backupEnabled) {
            return;
        }

        const { SessionBackup } = require('./sessionBackup');
        const interval = parseInt(process.env.BACKUP_INTERVAL || 300000);

        this._backupInterval = setInterval(() => {
            SessionBackup.backupSession(this.sessionName);
        }, interval);

        // Do immediate backup on connect
        SessionBackup.backupSession(this.sessionName);

        logger.debug({ sessionName: this.sessionName, interval }, 'Backup schedule started');
    }

    stopBackupSchedule() {
        if (this._backupInterval) {
            clearInterval(this._backupInterval);
            this._backupInterval = null;
            logger.debug({ sessionName: this.sessionName }, 'Backup schedule stopped');
        }
    }

    getDisconnectReason(statusCode) {
        const reasons = {
            [DisconnectReason.connectionClosed]: 'connection_closed',
            [DisconnectReason.connectionLost]: 'connection_lost',
            [DisconnectReason.connectionReplaced]: 'connection_replaced',
            [DisconnectReason.timedOut]: 'timed_out',
            [DisconnectReason.loggedOut]: 'logged_out',
            [DisconnectReason.badSession]: 'bad_session',
            [DisconnectReason.restartRequired]: 'restart_required',
            [DisconnectReason.multideviceMismatch]: 'multidevice_mismatch',
        };

        return reasons[statusCode] || 'unknown';
    }
}

module.exports = { BaileysDevice };
