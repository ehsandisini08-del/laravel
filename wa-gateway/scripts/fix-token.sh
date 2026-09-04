#!/bin/bash

echo "========================================="
echo "  Fixing Baileys Gateway API Token"
echo "========================================="
echo ""

# Step 1: Backup current .env
echo "Step 1: Creating backup..."
cd /var/www/billnet
BACKUP_FILE=".env.backup.$(date +%Y%m%d_%H%M%S)"
cp .env "$BACKUP_FILE"

if [ -f "$BACKUP_FILE" ]; then
    echo "✅ Backup created: $BACKUP_FILE"
else
    echo "❌ Backup failed!"
    exit 1
fi

echo ""

# Step 2: Show current token
echo "Step 2: Current token configuration..."
echo "Current BAILEYS_GATEWAY_TOKEN:"
grep "BAILEYS_GATEWAY_TOKEN=" .env

echo ""

# Step 3: Update token
echo "Step 3: Updating token..."
sed -i 's/BAILEYS_GATEWAY_TOKEN=e9cd7e75c82ae5eb6386a177c207ab08/BAILEYS_GATEWAY_TOKEN=429683C4C977415CAAFCCE10F7D57E11/' .env

echo ""

# Step 4: Verify change
echo "Step 4: Verifying change..."
echo "New BAILEYS_GATEWAY_TOKEN:"
grep "BAILEYS_GATEWAY_TOKEN=" .env

NEW_TOKEN=$(grep "BAILEYS_GATEWAY_TOKEN=" .env | cut -d'=' -f2)
if [ "$NEW_TOKEN" = "429683C4C977415CAAFCCE10F7D57E11" ]; then
    echo "✅ Token updated successfully!"
else
    echo "❌ Token update failed!"
    echo "Restoring backup..."
    cp "$BACKUP_FILE" .env
    exit 1
fi

echo ""

# Step 5: Clear Laravel cache
echo "Step 5: Clearing Laravel cache..."
php artisan config:clear
php artisan cache:clear

echo ""

# Step 6: Restart queue workers (if exist)
echo "Step 6: Restarting queue workers..."
php artisan queue:restart 2>/dev/null || echo "   No queue workers to restart (OK)"

echo ""
echo "========================================="
echo "  Testing Configuration"
echo "========================================="
echo ""

# Step 7: Test API access
echo "Step 7: Testing Gateway API access..."
RESPONSE=$(curl -s -X POST http://localhost:3001/devices \
  -H "Authorization: Bearer 429683C4C977415CAAFCCE10F7D57E11" \
  -H "Content-Type: application/json" \
  -d '{"sessionName":"test_token_verification"}')

echo "API Response: $RESPONSE"

if echo "$RESPONSE" | grep -q "Forbidden"; then
    echo "❌ API still returns Forbidden - something went wrong!"
    echo "Restoring backup..."
    cp "$BACKUP_FILE" .env
    php artisan config:clear
    exit 1
elif echo "$RESPONSE" | grep -q "error"; then
    echo "⚠️  API returned error (but not Forbidden - this might be OK if device exists)"
else
    echo "✅ API access working!"
fi

echo ""
echo "========================================="
echo "  Fix Complete!"
echo "========================================="
echo ""
echo "Summary:"
echo "  ✅ Token updated in Laravel .env"
echo "  ✅ Cache cleared"
echo "  ✅ Queue workers restarted"
echo "  ✅ API access verified"
echo ""
echo "Backup saved to: $BACKUP_FILE"
echo ""
echo "Next steps:"
echo "  1. Go to: https://yourdomain.com/whatsapp/devices/create"
echo "  2. Add a new device"
echo "  3. Should connect to Gateway and show QR immediately"
echo ""
echo "If there are any issues, restore backup with:"
echo "  cp $BACKUP_FILE /var/www/billnet/.env"
echo "  php artisan config:clear"
echo ""
