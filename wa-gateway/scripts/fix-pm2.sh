#!/bin/bash

echo "========================================="
echo "  Fixing WA Gateway PM2 Configuration"
echo "========================================="
echo ""

# Stop current PM2 process
echo "1. Stopping current PM2 processes..."
pm2 delete wa-gateway 2>/dev/null || echo "   No wa-gateway process found"
pm2 kill

echo ""
echo "2. Checking port 3001..."
PORT_PID=$(lsof -ti:3001)
if [ ! -z "$PORT_PID" ]; then
    echo "   Port 3001 is in use by PID: $PORT_PID"
    echo "   Killing process..."
    kill -9 $PORT_PID
    sleep 2
    echo "   ✓ Port 3001 freed"
else
    echo "   ✓ Port 3001 is free"
fi

echo ""
echo "3. Starting WA Gateway with fixed configuration..."
pm2 start ecosystem.config.js

sleep 3

echo ""
echo "4. Saving PM2 configuration..."
pm2 save

echo ""
echo "5. Checking status..."
pm2 status

echo ""
echo "========================================="
echo "  Checking logs for errors..."
echo "========================================="
sleep 2
pm2 logs wa-gateway --lines 20 --nostream

echo ""
echo "========================================="
echo "  Verification"
echo "========================================="
echo ""

# Check if process is running
if pm2 list | grep -q "wa-gateway.*online"; then
    echo "✅ WA Gateway is running"
else
    echo "❌ WA Gateway failed to start"
    exit 1
fi

# Check mode
MODE=$(pm2 show wa-gateway 2>/dev/null | grep "exec mode" | awk '{print $4}')
if [ "$MODE" = "fork_mode" ]; then
    echo "✅ Running in fork mode (correct)"
else
    echo "⚠️  Running in mode: $MODE"
fi

# Test API
echo ""
echo "Testing API endpoint..."
HEALTH_CHECK=$(curl -s http://localhost:3001/health 2>/dev/null)
if echo "$HEALTH_CHECK" | grep -q "status"; then
    echo "✅ Health endpoint responding"
    echo "   Response: $HEALTH_CHECK"
else
    echo "❌ Health endpoint not responding"
fi

echo ""
echo "========================================="
echo "  Fix Complete!"
echo "========================================="
echo ""
echo "Commands:"
echo "  - Check status:  pm2 status"
echo "  - View logs:     pm2 logs wa-gateway"
echo "  - Monitor:       pm2 monit"
echo "  - Restart:       pm2 restart wa-gateway"
echo ""
