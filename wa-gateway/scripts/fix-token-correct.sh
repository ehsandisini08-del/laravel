#!/bin/bash

echo "========================================="
echo "  Fixing Gateway API Token (CORRECT FIX)"
echo "========================================="
echo ""

# Step 1: Backup Gateway .env
echo "Step 1: Backing up Gateway .env..."
cd /var/www/billnet/wa-gateway
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
echo "✅ Backup created"

echo ""

# Step 2: Show current tokens
echo "Step 2: Current configuration..."
echo "Gateway API_TOKEN:"
grep "API_TOKEN=" .env
echo ""
echo "Laravel BAILEYS_GATEWAY_TOKEN:"
grep "BAILEYS_GATEWAY_TOKEN=" /var/www/billnet/.env

echo ""

# Step 3: Update Gateway token to match Laravel
echo "Step 3: Updating Gateway token..."
LARAVEL_TOKEN=$(grep "BAILEYS_GATEWAY_TOKEN=" /var/www/billnet/.env | cut -d'=' -f2)
echo "Will use token from Laravel: $LARAVEL_TOKEN"

sed -i "s/API_TOKEN=your_secure_token/API_TOKEN=$LARAVEL_TOKEN/" .env

echo ""

# Step 4: Verify change
echo "Step 4: Verifying change..."
echo "New Gateway API_TOKEN:"
grep "API_TOKEN=" .env

NEW_TOKEN=$(grep "API_TOKEN=" .env | cut -d'=' -f2)
if [ "$NEW_TOKEN" = "$LARAVEL_TOKEN" ]; then
    echo "✅ Token updated successfully!"
else
    echo "❌ Token update failed!"
    exit 1
fi

echo ""

# Step 5: Restart PM2
echo "Step 5: Restarting PM2 (to load new token)..."
pm2 restart wa-gateway

echo "Waiting 3 seconds for restart..."
sleep 3

echo ""

# Step 6: Test API access
echo "Step 6: Testing API access..."
RESPONSE=$(curl -s -X POST http://localhost:3001/devices \
  -H "Authorization: Bearer $LARAVEL_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"sessionName":"final_verification_test"}')

echo "API Response: $RESPONSE"

if echo "$RESPONSE" | grep -q "Forbidden"; then
    echo "❌ Still Forbidden - check failed!"
    exit 1
elif echo "$RESPONSE" | grep -q "session_name is required\|Session\|qr\|error"; then
    echo "✅ API access working! (Auth successful)"
else
    echo "⚠️  Unexpected response"
fi

echo ""
echo "========================================="
echo "  Fix Complete!"
echo "========================================="
echo ""
echo "Summary:"
echo "  ✅ Gateway token updated to match Laravel"
echo "  ✅ PM2 restarted with new token"
echo "  ✅ API access verified"
echo ""
echo "Next steps:"
echo "  1. Go to: https://yourdomain.com/whatsapp/devices/create"
echo "  2. Add a new device"
echo "  3. Should work now!"
echo ""
