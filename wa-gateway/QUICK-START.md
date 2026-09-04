# 🚀 Quick Start Guide - WhatsApp Gateway Always-On

## Development (Local)

### 1. Install Dependencies
```bash
cd wa-gateway
npm install
```

### 2. Configure Environment
```bash
# Copy .env example (if not exists)
cp .env.example .env

# Edit .env - set your API token
nano .env
```

### 3. Start Gateway

**Option A: With PM2 (Recommended)**
```bash
# Install PM2
npm run pm2:install

# Start
npm run pm2:start

# Check status
npm run pm2:status

# View logs
npm run pm2:logs
```

**Option B: Without PM2 (Development)**
```bash
npm start
```

### 4. Connect Device

1. Open Laravel app: `http://localhost:8000/whatsapp/devices`
2. Click "Add Device"
3. Enter session name (e.g., "default")
4. Scan QR code with WhatsApp

### 5. Monitor

Open monitoring dashboard:
```
http://localhost:8000/whatsapp/monitoring
```

✅ Done! Gateway now running with auto-reconnect, health monitoring, and backup!

---

## Production (Server)

### 1. SSH to Server
```bash
ssh user@yourserver.com
cd /var/www/billnet
```

### 2. Update Code
```bash
git pull origin main
cd wa-gateway
npm install
```

### 3. Install PM2 Globally
```bash
npm install pm2 -g
```

### 4. Configure Environment
```bash
nano .env
# Set production values:
# - WEBHOOK_URL=https://yourdomain.com/webhooks/whatsapp
# - API_TOKEN=your_secure_token
```

### 5. Start with PM2
```bash
pm2 start ecosystem.config.js

# Save process list
pm2 save

# Setup auto-start on boot
pm2 startup
# Follow the instructions shown
```

### 6. Setup Supervisor (Optional - Double Protection)
```bash
sudo nano /etc/supervisor/conf.d/wa-gateway.conf
```

Add:
```ini
[program:wa-gateway]
process_name=%(program_name)s
directory=/var/www/billnet/wa-gateway
command=/usr/bin/pm2 start ecosystem.config.js --no-daemon
user=www-data
autostart=true
autorestart=true
stopaslocal=true
stopwaitsecs=10
stdout_logfile=/var/log/supervisor/wa-gateway.log
stderr_logfile=/var/log/supervisor/wa-gateway-error.log
```

Reload:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start wa-gateway
```

### 7. Verify

```bash
# Check PM2 status
pm2 status

# Check logs
pm2 logs wa-gateway --lines 50

# Test API
curl http://localhost:3001/health

# Check monitoring
curl http://localhost:3001/monitoring/status \
  -H "Authorization: Bearer YOUR_TOKEN"
```

✅ Production deployment complete!

---

## Common Commands

### PM2 Management
```bash
# Status
pm2 status

# Logs (real-time)
pm2 logs wa-gateway

# Logs (last 100 lines)
pm2 logs wa-gateway --lines 100 --nostream

# Restart
pm2 restart wa-gateway

# Stop
pm2 stop wa-gateway

# Monitoring dashboard
pm2 monit

# Delete
pm2 delete wa-gateway
```

### Monitoring
```bash
# Health check
curl http://localhost:3001/health

# Device status
curl http://localhost:3001/monitoring/status \
  -H "Authorization: Bearer YOUR_TOKEN"

# Statistics
curl http://localhost:3001/monitoring/statistics/default \
  -H "Authorization: Bearer YOUR_TOKEN"

# Queue status
curl http://localhost:3001/monitoring/queue/default \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Manual Actions
```bash
# Force reconnect
curl -X POST http://localhost:3001/monitoring/reconnect/default \
  -H "Authorization: Bearer YOUR_TOKEN"

# Create backup
curl -X POST http://localhost:3001/monitoring/backup/default \
  -H "Authorization: Bearer YOUR_TOKEN"

# Restore from backup
curl -X POST http://localhost:3001/monitoring/restore/default \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"backupIndex": 0}'
```

---

## Troubleshooting

### Gateway not starting?

```bash
# Check port not in use
netstat -tuln | grep 3001

# Check PM2 logs
pm2 logs wa-gateway --lines 100

# Try restart
pm2 restart wa-gateway
```

### Device disconnecting frequently?

1. Check internet connection
2. Check monitoring dashboard for alerts
3. Verify WhatsApp account not banned
4. Try restore from backup:
   ```bash
   curl -X POST http://localhost:3001/monitoring/restore/default \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"backupIndex": 0}'
   ```

### PM2 not auto-starting after reboot?

```bash
# Check startup script
pm2 startup

# Save config
pm2 save

# Test reboot
sudo reboot
# Wait, then check:
pm2 status
```

---

## Features Enabled

✅ **Auto-Reconnect** - Unlimited attempts (3s → 60s)  
✅ **Health Monitoring** - Check every 30 seconds  
✅ **Session Backup** - Every 5 minutes, keep 3 backups  
✅ **Message Queue** - Unlimited size, auto-retry  
✅ **PM2 Auto-Restart** - Restart if crash (3s downtime)  
✅ **Monitoring Dashboard** - Real-time stats & alerts  
✅ **Connection Tracking** - Uptime, history, statistics  

**Expected Uptime: 99.9%** 🎉

---

## Need Help?

1. Check logs: `pm2 logs wa-gateway`
2. Check monitoring dashboard: `/whatsapp/monitoring`
3. Check API health: `curl http://localhost:3001/health`
4. Review README.md for detailed documentation
