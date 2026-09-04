#!/bin/bash
echo "==========================================="
echo "  Installing PM2 for WA Gateway"
echo "==========================================="

# Install PM2 globally
echo "Installing PM2 globally..."
npm install pm2 -g

# Create logs directory
echo "Creating logs directory..."
mkdir -p ../logs

# Go to wa-gateway directory
cd ..

echo ""
echo "PM2 installed successfully!"
echo ""
echo "Next steps:"
echo "  1. Start gateway: npm run pm2:start"
echo "  2. Check status:  npm run pm2:status"
echo "  3. View logs:     npm run pm2:logs"
echo "  4. Save config:   pm2 save"
echo "  5. Auto-startup:  pm2 startup"
echo ""
echo "==========================================="
