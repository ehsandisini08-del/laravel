require('dotenv').config();

const express = require('express');
const { authMiddleware } = require('./middleware/auth');
const { createLogger } = require('./utils/logger');
const deviceRoutes = require('./routes/devices');
const messageRoutes = require('./routes/messages');
const monitoringRoutes = require('./routes/monitoring');
const { SessionManager } = require('./services/sessionManager');
const { HealthMonitor } = require('./services/healthMonitor');
const { SessionBackup } = require('./services/sessionBackup');

const logger = createLogger();
const app = express();
const PORT = process.env.PORT || 3001;

app.use(express.json({ limit: '50mb' }));

app.use(authMiddleware);

app.use('/devices', deviceRoutes);
app.use('/messages', messageRoutes);
app.use('/monitoring', monitoringRoutes);

app.get('/health', (req, res) => {
    res.json({ 
        status: 'ok', 
        uptime: process.uptime(),
        memory: process.memoryUsage(),
        timestamp: new Date().toISOString(),
    });
});

process.on('SIGTERM', async () => {
    logger.info('SIGTERM received, cleaning up...');
    HealthMonitor.stopMonitoring();
    SessionBackup.stopScheduler();
    await SessionManager.cleanup();
    process.exit(0);
});

process.on('SIGINT', async () => {
    logger.info('SIGINT received, cleaning up...');
    HealthMonitor.stopMonitoring();
    SessionBackup.stopScheduler();
    await SessionManager.cleanup();
    process.exit(0);
});

process.on('unhandledRejection', (reason) => {
    logger.error({ reason: reason?.message || reason }, 'Unhandled rejection (ignored)');
});

process.on('uncaughtException', (err) => {
    logger.error({ error: err.message, stack: err.stack }, 'Uncaught exception (ignored)');
});

app.listen(PORT, () => {
    logger.info(`WA Gateway running on port ${PORT}`);
    
    // Restore all sessions
    SessionManager.restoreAll();
    
    // Start health monitoring
    HealthMonitor.startMonitoring(SessionManager);
    logger.info('Health monitoring started (30s interval)');
    
    // Start backup scheduler
    SessionBackup.startGlobalBackupScheduler();
    logger.info('Session backup scheduler started (5min interval)');
    
    logger.info('✅ WA Gateway Always-On Edition ready!');
});