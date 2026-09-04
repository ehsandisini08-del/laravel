const path = require('path');
const fs = require('fs');
const { createLogger } = require('../utils/logger');
const { AlertManager } = require('../utils/alerts');

const logger = createLogger();

class MessageQueue {
    constructor(sessionName) {
        this.sessionName = sessionName;
        this.queue = [];
        this.processing = false;
        this.queueFile = path.resolve(process.env.SESSION_DIR || './sessions', 'queues', `${sessionName}.json`);
        
        // Load queue from disk on initialization
        this.loadQueueFromDisk();
    }

    async addToQueue(message) {
        const queueEnabled = process.env.QUEUE_ENABLED !== 'false';
        if (!queueEnabled) {
            throw new Error('Message queue is disabled');
        }

        const queueItem = {
            id: `msg_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
            sessionName: this.sessionName,
            type: message.type || 'text',
            recipient: message.recipient,
            content: message.content,
            attempts: 0,
            maxAttempts: parseInt(process.env.QUEUE_MAX_RETRIES || 5),
            createdAt: new Date().toISOString(),
            lastAttemptAt: null,
            status: 'pending', // pending, processing, sent, failed
        };

        this.queue.push(queueItem);

        logger.info({
            sessionName: this.sessionName,
            messageId: queueItem.id,
            recipient: message.recipient,
            queueLength: this.queue.length,
        }, 'Message added to queue');

        // Save to disk
        await this.saveQueueToDisk();

        // Check if queue is growing too large
        if (this.queue.length > 50) {
            AlertManager.createAlert(
                AlertManager.TYPES.QUEUE_GROWING,
                this.sessionName,
                `Message queue growing: ${this.queue.length} messages pending`,
                'medium',
                { queueLength: this.queue.length }
            );
        }

        return queueItem;
    }

    async processQueue() {
        if (this.processing) {
            logger.debug({ sessionName: this.sessionName }, 'Queue already processing');
            return;
        }

        const pendingMessages = this.queue.filter(m => m.status === 'pending' || m.status === 'processing');
        
        if (pendingMessages.length === 0) {
            logger.debug({ sessionName: this.sessionName }, 'No messages in queue to process');
            return;
        }

        logger.info({
            sessionName: this.sessionName,
            pendingCount: pendingMessages.length,
        }, 'Starting queue processing');

        this.processing = true;

        try {
            // Process messages one by one
            for (const message of pendingMessages) {
                const success = await this.processMessage(message);
                
                if (success) {
                    // Remove from queue
                    this.queue = this.queue.filter(m => m.id !== message.id);
                } else {
                    // Check if max attempts reached
                    if (message.attempts >= message.maxAttempts) {
                        message.status = 'failed';
                        logger.warn({
                            sessionName: this.sessionName,
                            messageId: message.id,
                            attempts: message.attempts,
                        }, 'Message failed after max attempts');
                    }
                }

                // Small delay between messages
                await new Promise(resolve => setTimeout(resolve, 1000));
            }

            // Save updated queue to disk
            await this.saveQueueToDisk();

            logger.info({
                sessionName: this.sessionName,
                remaining: this.queue.filter(m => m.status === 'pending').length,
            }, 'Queue processing completed');

        } catch (error) {
            logger.error({
                sessionName: this.sessionName,
                error: error.message,
            }, 'Error during queue processing');
        } finally {
            this.processing = false;
        }
    }

    async processMessage(message) {
        message.status = 'processing';
        message.attempts++;
        message.lastAttemptAt = new Date().toISOString();

        logger.debug({
            sessionName: this.sessionName,
            messageId: message.id,
            attempt: message.attempts,
        }, 'Processing queued message');

        try {
            // Get device from session manager
            const { SessionManager } = require('./sessionManager');
            const device = SessionManager.get(this.sessionName);

            if (!device || device.status !== 'connected') {
                logger.debug({
                    sessionName: this.sessionName,
                    messageId: message.id,
                }, 'Device not connected, will retry later');
                message.status = 'pending';
                return false;
            }

            // Send message based on type
            let result;
            
            switch (message.type) {
                case 'text':
                    result = await this.sendText(device, message.recipient, message.content);
                    break;
                    
                case 'image':
                    result = await this.sendImage(device, message.recipient, message.content.url, message.content.caption);
                    break;
                    
                case 'document':
                    result = await this.sendDocument(device, message.recipient, message.content.url, message.content.fileName);
                    break;
                    
                default:
                    throw new Error(`Unknown message type: ${message.type}`);
            }

            if (result) {
                message.status = 'sent';
                logger.info({
                    sessionName: this.sessionName,
                    messageId: message.id,
                    recipient: message.recipient,
                }, 'Queued message sent successfully');
                return true;
            }

            return false;

        } catch (error) {
            logger.error({
                sessionName: this.sessionName,
                messageId: message.id,
                error: error.message,
                attempt: message.attempts,
            }, 'Failed to send queued message');

            message.status = 'pending';
            return false;
        }
    }

    async sendText(device, phone, text) {
        const jid = `${phone}@s.whatsapp.net`;
        const result = await device.sock.sendMessage(jid, { text });
        device._lastActivityTime = Date.now();
        return result;
    }

    async sendImage(device, phone, imageUrl, caption) {
        const jid = `${phone}@s.whatsapp.net`;
        const result = await device.sock.sendMessage(jid, {
            image: { url: imageUrl },
            caption: caption || '',
        });
        device._lastActivityTime = Date.now();
        return result;
    }

    async sendDocument(device, phone, documentUrl, fileName) {
        const jid = `${phone}@s.whatsapp.net`;
        const result = await device.sock.sendMessage(jid, {
            document: { url: documentUrl },
            fileName: fileName || 'document',
            mimetype: 'application/octet-stream',
        });
        device._lastActivityTime = Date.now();
        return result;
    }

    getQueueStatus() {
        const pending = this.queue.filter(m => m.status === 'pending').length;
        const processing = this.queue.filter(m => m.status === 'processing').length;
        const failed = this.queue.filter(m => m.status === 'failed').length;

        return {
            sessionName: this.sessionName,
            total: this.queue.length,
            pending,
            processing,
            failed,
            isProcessing: this.processing,
            messages: this.queue.map(m => ({
                id: m.id,
                type: m.type,
                recipient: m.recipient,
                status: m.status,
                attempts: m.attempts,
                maxAttempts: m.maxAttempts,
                createdAt: m.createdAt,
                lastAttemptAt: m.lastAttemptAt,
            })),
        };
    }

    clearQueue() {
        const count = this.queue.length;
        this.queue = [];
        this.saveQueueToDisk();
        
        logger.info({ sessionName: this.sessionName, cleared: count }, 'Queue cleared');
        
        return count;
    }

    clearFailedMessages() {
        const before = this.queue.length;
        this.queue = this.queue.filter(m => m.status !== 'failed');
        const cleared = before - this.queue.length;
        
        if (cleared > 0) {
            this.saveQueueToDisk();
            logger.info({ sessionName: this.sessionName, cleared }, 'Failed messages cleared');
        }
        
        return cleared;
    }

    async saveQueueToDisk() {
        try {
            const queueDir = path.dirname(this.queueFile);
            
            if (!fs.existsSync(queueDir)) {
                fs.mkdirSync(queueDir, { recursive: true });
            }

            const data = JSON.stringify(this.queue, null, 2);
            fs.writeFileSync(this.queueFile, data, 'utf8');

            logger.debug({
                sessionName: this.sessionName,
                queueLength: this.queue.length,
            }, 'Queue saved to disk');

        } catch (error) {
            logger.error({
                sessionName: this.sessionName,
                error: error.message,
            }, 'Failed to save queue to disk');
        }
    }

    loadQueueFromDisk() {
        try {
            if (!fs.existsSync(this.queueFile)) {
                logger.debug({ sessionName: this.sessionName }, 'No queue file found');
                return;
            }

            const data = fs.readFileSync(this.queueFile, 'utf8');
            this.queue = JSON.parse(data);

            // Reset processing status on load (in case of crash)
            for (const message of this.queue) {
                if (message.status === 'processing') {
                    message.status = 'pending';
                }
            }

            logger.info({
                sessionName: this.sessionName,
                loaded: this.queue.length,
            }, 'Queue loaded from disk');

        } catch (error) {
            logger.error({
                sessionName: this.sessionName,
                error: error.message,
            }, 'Failed to load queue from disk');
            this.queue = [];
        }
    }

    static getAllQueueStats() {
        try {
            const queueDir = path.resolve(process.env.SESSION_DIR || './sessions', 'queues');

            if (!fs.existsSync(queueDir)) {
                return [];
            }

            const queueFiles = fs.readdirSync(queueDir)
                .filter(file => file.endsWith('.json'));

            const stats = [];

            for (const file of queueFiles) {
                const sessionName = file.replace('.json', '');
                const queueFile = path.join(queueDir, file);
                
                try {
                    const data = JSON.parse(fs.readFileSync(queueFile, 'utf8'));
                    
                    const pending = data.filter(m => m.status === 'pending').length;
                    const processing = data.filter(m => m.status === 'processing').length;
                    const failed = data.filter(m => m.status === 'failed').length;

                    stats.push({
                        sessionName,
                        total: data.length,
                        pending,
                        processing,
                        failed,
                    });
                } catch (error) {
                    logger.error({ file, error: error.message }, 'Failed to read queue file');
                }
            }

            return stats;

        } catch (error) {
            logger.error({ error: error.message }, 'Failed to get queue stats');
            return [];
        }
    }
}

module.exports = { MessageQueue };
