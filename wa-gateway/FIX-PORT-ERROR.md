# 🔧 QUICK FIX - Port 3001 EADDRINUSE Error

## Problem
```
Error: bind EADDRINUSE null:3001
```

PM2 running in **cluster mode** instead of **fork mode**, causing multiple processes to try binding to same port.

---

## ✅ SOLUTION APPLIED

### File Fixed: `ecosystem.config.js`

**Added:** `exec_mode: 'fork'` on line 6

**Why:** WhatsApp Gateway tidak support cluster mode karena session management harus single instance.

---

## 🚀 HOW TO APPLY FIX ON SERVER

### Option 1: Auto Fix Script (Recommended)

```bash
cd /var/www/billnet/wa-gateway

# Pull latest changes
git pull origin main

# Run fix script
bash scripts/fix-pm2.sh
```

Script akan otomatis:
- ✅ Stop PM2 process
- ✅ Kill port 3001 if needed
- ✅ Start with correct config
- ✅ Verify running correctly

---

### Option 2: Manual Fix

```bash
cd /var/www/billnet/wa-gateway

# Stop current process
pm2 delete wa-gateway
pm2 kill

# Check & kill port 3001
sudo lsof -i :3001
# If found: sudo kill -9 <PID>

# Pull latest config
git pull origin main

# Start with fixed config
pm2 start ecosystem.config.js

# Save configuration
pm2 save

# Check status
pm2 status

# Check logs (should be no errors)
pm2 logs wa-gateway --lines 30
```

---

## ✅ VERIFICATION

After fix, verify:

```bash
# 1. Check status
pm2 status
# Should show: online, mode: fork

# 2. Check mode
pm2 show wa-gateway | grep "exec mode"
# Should show: fork_mode

# 3. Check logs
pm2 logs wa-gateway --lines 20
# Should show:
#   ✅ WA Gateway running on port 3001
#   ✅ Health monitoring started
#   ✅ Session backup scheduler started

# 4. Test API
curl http://localhost:3001/health
# Should return: {"status":"ok","uptime":...}
```

---

## 📊 Expected Result

**Before Fix:**
```
┌────┬───────────────┬──────────┬──────┬───────────┬──────────┐
│ id │ name          │ mode     │ ↺    │ status    │ memory   │
├────┼───────────────┼──────────┼──────┼───────────┼──────────┤
│ 0  │ wa-gateway    │ cluster  │ 15   │ errored   │ 0mb      │
└────┴───────────────┴──────────┴──────┴───────────┴──────────┘
```

**After Fix:**
```
┌────┬───────────────┬──────────┬──────┬───────────┬──────────┐
│ id │ name          │ mode     │ ↺    │ status    │ memory   │
├────┼───────────────┼──────────┼──────┼───────────┼──────────┤
│ 0  │ wa-gateway    │ fork     │ 0    │ online    │ 120mb    │
└────┴───────────────┴──────────┴──────┴───────────┴──────────┘
```

---

## 🐛 If Still Fails

### Check port is really free:
```bash
sudo netstat -tulpn | grep :3001
```

### Check .env configuration:
```bash
cat .env | grep PORT
# Should show: PORT=3001
```

### Check if another wa-gateway running:
```bash
ps aux | grep "node.*wa-gateway"
# Kill any old processes: kill -9 <PID>
```

### Fresh restart:
```bash
pm2 delete all
pm2 kill
sudo killall node
pm2 start ecosystem.config.js
pm2 save
```

---

## 📝 Root Cause

**Original `ecosystem.config.js` was missing:**
```javascript
exec_mode: 'fork',  // ← This line was missing
```

**PM2 default behavior:**
- If `exec_mode` not specified → defaults to cluster mode
- Cluster mode → multiple workers try to bind same port → EADDRINUSE error

**Fix:**
- Explicitly set `exec_mode: 'fork'`
- Single process, single port binding
- Perfect for WhatsApp Gateway

---

## ✅ Status

- [x] File fixed: `ecosystem.config.js`
- [x] Auto fix script created: `scripts/fix-pm2.sh`
- [x] Documentation updated: This file
- [x] Ready to deploy

---

**Apply fix now on server to resolve the error!** 🚀
