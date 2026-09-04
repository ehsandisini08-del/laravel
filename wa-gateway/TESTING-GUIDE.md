# 🧪 Testing Guide - WhatsApp Gateway Always-On

## Pre-Testing Checklist

### ✅ Prerequisites
- [ ] Node.js 20+ installed
- [ ] npm 10+ installed
- [ ] PM2 installed (`npm run pm2:install`)
- [ ] `.env` configured
- [ ] Laravel app running

---

## Test Suite

### 1️⃣ Installation Test

**Test PM2 Installation**
```bash
cd wa-gateway

# Test syntax of all files
node -c src/index.js
node -c src/services/baileysDevice.js
node -c src/services/healthMonitor.js
node -c src/services/sessionBackup.js
node -c src/services/messageQueue.js
node -c src/services/connectionStateManager.js
node -c src/utils/alerts.js
node -c src/routes/monitoring.js

# Install dependencies
npm install

# Install PM2
npm run pm2:install

# Expected: PM2 installed successfully
```

**Expected Output:**
```
✅ All syntax checks pass
✅ Dependencies installed
✅ PM2 installed globally
```

---

### 2️⃣ Startup Test

**Start Gateway with PM2**
```bash
npm run pm2:start

# Wait 5 seconds
Start-Sleep -Seconds 5

# Check status
npm run pm2:status
```

**Expected Output:**
```
┌────┬────────────────┬─────────┬──────┬───────┬────────┬─────────┐
│ id │ name           │ status  │ cpu  │ mem   │ uptime │ restart │
├────┼────────────────┼─────────┼──────┼───────┼────────┼─────────┤
│ 0  │ wa-gateway     │ online  │ 0%   │ 100MB │ 5s     │ 0       │
└────┴────────────────┴─────────┴──────┴───────┴────────┴─────────┘
```

**Verify Logs:**
```bash
npm run pm2:logs -- --lines 20
```

**Expected Log Messages:**
```
✅ WA Gateway running on port 3001
✅ Health monitoring started (30s interval)
✅ Session backup scheduler started (5min interval)
✅ WA Gateway Always-On Edition ready!
```

---

### 3️⃣ Health Check Test

**Test Health Endpoint**
```bash
# Test health endpoint
curl http://localhost:3001/health
```

**Expected Response:**
```json
{
  "status": "ok",
  "uptime": 10.5,
  "memory": {
    "rss": 104857600,
    "heapTotal": 52428800,
    "heapUsed": 31457280
  },
  "timestamp": "2026-09-04T10:25:52.792Z"
}
```

---

### 4️⃣ API Authentication Test

**Test with valid token**
```bash
curl http://localhost:3001/monitoring/status \
  -H "Authorization: Bearer 429683C4C977415CAAFCCE10F7D57E11"
```

**Expected:** Status 200, JSON response

**Test without token**
```bash
curl http://localhost:3001/monitoring/status
```

**Expected:** Status 401, Unauthorized

---

### 5️⃣ Device Connection Test

**Connect Device**
1. Open Laravel app: `http://localhost:8000/whatsapp/devices`
2. Click "Add Device"
3. Enter session name: `default`
4. Scan QR code with WhatsApp

**Verify Connection:**
```bash
curl http://localhost:3001/monitoring/status \
  -H "Authorization: Bearer 429683C4C977415CAAFCCE10F7D57E11"
```

**Expected Response:**
```json
{
  "success": true,
  "devices": [
    {
      "sessionName": "default",
      "status": "connected",
      "phoneNumber": "628123456789",
      "profileName": "John Doe",
      "lastSeen": "2026-09-04T10:25:00.000Z",
      "isHealthy": true,
      "qrCode": null,
      "reconnectAttempts": 0
    }
  ]
}
```

---

### 6️⃣ Health Monitoring Test

**Wait for health check (30 seconds)**
```bash
# Wait 30 seconds
Start-Sleep -Seconds 30

# Check logs
npm run pm2:logs -- --lines 30
```

**Expected Log:**
```
Health check started
Checking device activity
Health check passed
```

---

### 7️⃣ Auto-Reconnect Test

**Test Network Disconnect**

**Method 1: Turn off WiFi**
```
1. Turn off WiFi/disconnect ethernet
2. Wait 5 seconds
3. Check logs: npm run pm2:logs
4. Turn on WiFi/reconnect ethernet
5. Wait 10 seconds
6. Check logs again
```

**Expected Logs:**
```
❌ Connection closed
⏳ Reconnecting... (attempt #1, next retry in 3000ms)
⏳ Scheduling reconnect
✅ Connected (phone: 628123456789)
✅ Health monitoring started
```

**Verify in Dashboard:**
- Check monitoring dashboard: `http://localhost:8000/whatsapp/monitoring`
- Should show brief disconnect
- Should show successful reconnect
- Uptime percentage should be calculated

---

### 8️⃣ Session Backup Test

**Wait for backup (5 minutes) OR trigger manual backup**

**Manual Backup:**
```bash
curl -X POST http://localhost:3001/monitoring/backup/default \
  -H "Authorization: Bearer 429683C4C977415CAAFCCE10F7D57E11"
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Backup created successfully",
  "sessionName": "default"
}
```

**Verify Backup Created:**
```bash
# Check backups directory
ls -la wa-gateway/sessions/backups/default/
```

**Expected:** Directory with timestamp (e.g., `2026-09-04T10-25-00/`)

**List Backups via API:**
```bash
curl http://localhost:3001/monitoring/backups/default \
  -H "Authorization: Bearer 429683C4C977415CAAFCCE10F7D57E11"
```

**Expected Response:**
```json
{
  "success": true,
  "sessionName": "default",
  "backups": [
    {
      "name": "2026-09-04T10-25-00",
      "timestamp": "2026-09-04T10-25-00",
      "createdAt": "2026-09-04T10:25:00.000Z",
      "size": 45678,
      "fileCount": 3
    }
  ]
}
```

---

### 9️⃣ Message Queue Test

**Test Queue During Disconnect**

**Step 1: Disconnect device manually**
```bash
curl -X POST http://localhost:3001/devices/default/disconnect \
  -H "Authorization: Bearer 429683C4C977415CAAFCCE10F7D57E11"
```

**Step 2: Send message (should be queued)**
```bash
curl -X POST http://localhost:3001/messages \
  -H "Authorization: Bearer 429683C4C977415CAAFCCE10F7D57E11" \
  -H "Content-Type: application/json" \
  -d '{
    "sessionName": "default",
    "phone": "628123456789",
    "message": "Test message during disconnect"
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "queued": true,
  "message": "Message will be sent when device reconnects"
}
```

**Step 3: Check queue status**
```bash
curl http://localhost:3001/monitoring/queue/default \
  -H "Authorization: Bearer 429683C4C977415CAAFCCE10F7D57E11"
```

**Expected:**
```json
{
  "success": true,
  "queue": {
    "sessionName": "default",
    "total": 1,
    "pending": 1,
    "processing": 0,
    "failed": 0,
    "isProcessing": false,
    "messages": [...]
  }
}
```

**Step 4: Reconnect device**
```bash
curl -X POST http://localhost:3001/devices/default/connect \
  -H "Authorization: Bearer 429683C4C977415CAAFCCE10F7D57E11"
```

**Step 5: Verify message sent**
```bash
# Check logs
npm run pm2:logs -- --lines 50

# Check queue (should be empty)
curl http://localhost:3001/monitoring/queue/default \
  -H "Authorization: Bearer 429683C4C977415CAAFCCE10F7D57E11"
```

**Expected:** Queue is empty, message was sent

---

### 🔟 PM2 Auto-Restart Test

**Test Process Crash Recovery**

**Step 1: Kill the process**
```bash
# Get PM2 process ID
pm2 status

# Kill the process
pm2 delete wa-gateway

# Wait 3 seconds
Start-Sleep -Seconds 3

# Start again
npm run pm2:start

# Check status
pm2 status
```

**Expected:**
- PM2 shows process restarted
- Uptime reset to 0
- Status: online
- Restart count increased

**Verify Session Restored:**
```bash
curl http://localhost:3001/monitoring/status \
  -H "Authorization: Bearer 429683C4C977415CAAFCCE10F7D57E11"
```

**Expected:** Device reconnected automatically from saved session

---

### 1️⃣1️⃣ Monitoring Dashboard Test

**Open Dashboard**
```
http://localhost:8000/whatsapp/monitoring
```

**Verify:**
- [ ] Stats cards displaying (devices, uptime, alerts, queue)
- [ ] Device status card showing
- [ ] Connection status correct
- [ ] Reconnect button visible
- [ ] Backup button visible
- [ ] Auto-refresh countdown showing (10s)
- [ ] No JavaScript errors in console

**Test Reconnect Button:**
1. Click "Reconnect" button on device card
2. Confirm dialog
3. Should show success message
4. Device should reconnect

**Test Backup Button:**
1. Click "Backup Now" button
2. Should show success message
3. Verify backup created in API

**Test Auto-Refresh:**
1. Wait 10 seconds
2. Should see stats update automatically
3. Countdown should reset to 10

---

### 1️⃣2️⃣ Alert System Test

**Test Extended Disconnect Alert**

**Method: Block internet for > 5 minutes**
```
1. Disconnect internet
2. Wait 6 minutes
3. Check dashboard
4. Should see alert: "Device disconnected for X minutes"
5. Reconnect internet
6. Alert should disappear after reconnect
```

**Verify via API:**
```bash
curl http://localhost:3001/monitoring/alerts \
  -H "Authorization: Bearer 429683C4C977415CAAFCCE10F7D57E11"
```

**Expected (during disconnect):**
```json
{
  "success": true,
  "alerts": [
    {
      "type": "extended_disconnect",
      "sessionName": "default",
      "message": "Device disconnected for 6 minutes",
      "severity": "high",
      "timestamp": "2026-09-04T10:31:00.000Z",
      "duration": 360000
    }
  ]
}
```

---

### 1️⃣3️⃣ Statistics Test

**Get Detailed Statistics**
```bash
curl http://localhost:3001/monitoring/statistics/default \
  -H "Authorization: Bearer 429683C4C977415CAAFCCE10F7D57E11"
```

**Expected Response:**
```json
{
  "success": true,
  "statistics": {
    "sessionName": "default",
    "status": "connected",
    "currentUptime": 600000,
    "currentDisconnectDuration": 0,
    "totalUptime": 3600000,
    "totalDowntime": 45000,
    "uptimePercentage": 98.77,
    "totalConnects": 5,
    "totalDisconnects": 4,
    "lastConnect": "2026-09-04T10:25:00.000Z",
    "lastDisconnect": "2026-09-04T10:20:00.000Z",
    "avgReconnectTime": 8500,
    "longestUptime": 1800000
  }
}
```

**Verify:**
- [ ] uptimePercentage > 95%
- [ ] avgReconnectTime < 30000 (30s)
- [ ] Statistics make sense

---

### 1️⃣4️⃣ Connection History Test

**Get Connection History**
```bash
curl http://localhost:3001/monitoring/history/default?limit=10 \
  -H "Authorization: Bearer 429683C4C977415CAAFCCE10F7D57E11"
```

**Expected Response:**
```json
{
  "success": true,
  "sessionName": "default",
  "history": [
    {
      "event": "connect",
      "timestamp": "2026-09-04T10:25:00.000Z",
      "details": {
        "reconnectTime": 8500
      }
    },
    {
      "event": "disconnect",
      "timestamp": "2026-09-04T10:24:51.000Z",
      "reason": "connection_lost",
      "details": {
        "uptimeBeforeDisconnect": 300000
      }
    }
  ]
}
```

---

## 🎯 Success Criteria

All tests should pass:

- [x] ✅ Installation successful
- [x] ✅ Gateway starts with PM2
- [x] ✅ Health endpoint responds
- [x] ✅ API authentication works
- [x] ✅ Device connects successfully
- [x] ✅ Health monitoring active
- [x] ✅ Auto-reconnect works
- [x] ✅ Session backup created
- [x] ✅ Message queue works
- [x] ✅ PM2 auto-restart works
- [x] ✅ Dashboard accessible
- [x] ✅ Alerts working
- [x] ✅ Statistics accurate
- [x] ✅ History tracking works

---

## 📊 Performance Benchmarks

After testing, verify metrics:

**Memory Usage:**
```bash
pm2 info wa-gateway | grep memory
```
**Expected:** < 200 MB

**CPU Usage:**
```bash
pm2 monit
```
**Expected:** < 10% average

**Uptime:**
```bash
pm2 status
```
**Expected:** Running stable without restarts (except manual tests)

**Reconnect Time:**
- Check logs and dashboard statistics
- **Expected:** < 60 seconds average

---

## 🐛 If Tests Fail

### Gateway won't start
```bash
# Check syntax
node -c src/index.js

# Check dependencies
npm install

# Check port
netstat -tuln | grep 3001

# Check logs
pm2 logs wa-gateway --err
```

### Device won't connect
- Check WiFi/internet connection
- Check WhatsApp not banned
- Try new session name
- Check gateway logs

### Health check not running
- Check logs for "Health monitoring started"
- Verify HEALTH_CHECK_ENABLED=true in .env
- Wait 30 seconds after start

### Backup not created
- Check write permissions on sessions folder
- Verify BACKUP_ENABLED=true in .env
- Check disk space

### Queue not processing
- Verify device is connected
- Check queue status via API
- Check logs for errors

---

## ✅ Final Verification

After all tests pass:

```bash
# Check overall status
pm2 status

# Check memory & CPU
pm2 monit

# Check logs (should be clean)
pm2 logs wa-gateway --lines 100

# Check monitoring overview
curl http://localhost:3001/monitoring/overview \
  -H "Authorization: Bearer 429683C4C977415CAAFCCE10F7D57E11"
```

**Expected:** All green, no errors, stable operation

---

**🎉 Testing Complete! Gateway is READY FOR PRODUCTION!**
