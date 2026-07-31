const path = require('path');
const fs = require('fs');
const { makeWASocket, useMultiFileAuthState, DisconnectReason, fetchLatestBaileysVersion, makeCacheableSignalKeyStore } = require('@whiskeysockets/baileys');
const QRCode = require('qrcode');
const { createLogger } = require('../utils/logger');
const { sendWebhook } = require('./webhook');

const logger = createLogger();

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
        this._maxReconnectAttempts = 10;
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

        if (!fs.existsSync(this.sessionDir)) {
            fs.mkdirSync(this.sessionDir, { recursive: true });
        }

        const { state, saveCreds } = await useMultiFileAuthState(this.sessionDir);
        const { version } = await fetchLatestBaileysVersion();

        this.status = 'connecting';
        this.qrCode = null;

        const sock = makeWASocket({
            version,
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

                const user = sock.user;
                if (user) {
                    this.phoneNumber = user.id?.split(':')[0]?.replace('@s.whatsapp.net', '') || null;
                    this.profileName = user.name || null;
                }

                await sendWebhook('connected', this.sessionName, {
                    phone_number: this.phoneNumber,
                    profile_name: this.profileName,
                });

                logger.info({ sessionName: this.sessionName, phone: this.phoneNumber }, 'Connected');
            }

            if (connection === 'close') {
                const statusCode = lastDisconnect?.error?.output?.statusCode;
                const shouldReconnect = statusCode !== DisconnectReason.loggedOut;

                this.sock = null;

                if (shouldReconnect && this._reconnectAttempts < this._maxReconnectAttempts) {
                    this._reconnectAttempts++;
                    this.status = 'connecting';
                    logger.info({ sessionName: this.sessionName, attempt: this._reconnectAttempts }, 'Reconnecting...');

                    await sendWebhook('disconnected', this.sessionName, {
                        reason: 'connection_lost',
                        will_reconnect: true,
                    });

                    setTimeout(() => this.connect(), 3000 * Math.min(this._reconnectAttempts, 5));
                } else if (statusCode === DisconnectReason.loggedOut) {
                    this.status = 'logged_out';
                    this.qrCode = null;
                    await sendWebhook('disconnected', this.sessionName, {
                        reason: 'logged_out',
                        will_reconnect: false,
                    });
                    logger.info({ sessionName: this.sessionName }, 'Logged out');
                } else {
                    this.status = 'disconnected';
                    await sendWebhook('disconnected', this.sessionName, {
                        reason: 'max_reconnect_exceeded',
                        will_reconnect: false,
                    });
                    logger.warn({ sessionName: this.sessionName }, 'Max reconnect attempts exceeded');
                }
            }
        });

        sock.ev.on('messages.upsert', async (m) => {
            for (const msg of m.messages) {
                if (!msg.key.fromMe) {
                    await sendWebhook('message_received', this.sessionName, {
                        message: msg,
                    });
                }
            }
        });
    }

    async disconnect() {
        if (this.sock) {
            this.sock.end();
            this.sock = null;
        }
        this.status = 'disconnected';
        this.lastSeen = new Date().toISOString();
    }

    async logout() {
        if (this.sock) {
            await this.sock.logout();
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
        if (!this.sock || this.status !== 'connected') {
            throw new Error('Device not connected');
        }

        const jid = `${phone}@s.whatsapp.net`;
        const result = await this.sock.sendMessage(jid, { text });

        await sendWebhook('message_sent', this.sessionName, {
            message_id: result.key.id,
            phone,
            status: 'sent',
        });

        return result;
    }

    async sendImage(phone, imageUrl, caption) {
        if (!this.sock || this.status !== 'connected') {
            throw new Error('Device not connected');
        }

        const jid = `${phone}@s.whatsapp.net`;
        const result = await this.sock.sendMessage(jid, {
            image: { url: imageUrl },
            caption: caption || '',
        });

        return result;
    }

    async sendDocument(phone, documentUrl, fileName) {
        if (!this.sock || this.status !== 'connected') {
            throw new Error('Device not connected');
        }

        const jid = `${phone}@s.whatsapp.net`;
        const result = await this.sock.sendMessage(jid, {
            document: { url: documentUrl },
            fileName: fileName || 'document',
            mimetype: 'application/octet-stream',
        });

        return result;
    }

    async cleanup() {
        if (this.sock) {
            this.sock.end();
            this.sock = null;
        }
    }
}

module.exports = { BaileysDevice };