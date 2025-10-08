#!/bin/bash

# Simple user data script to set up the Lightsail instance for Node.js application deployment
# This script runs when the instance first boots up

set -e

# Variables
APP_NAME="${app_name}"
APP_DIR="/var/www/$APP_NAME"
LOG_FILE="/var/log/user-data.log"

# Simple logging approach - avoid complex redirection
echo "Starting user data script at $(date)" | tee -a "$LOG_FILE"

# Update system packages
echo "Updating system packages..." | tee -a "$LOG_FILE"
apt-get update -y >> "$LOG_FILE" 2>&1
apt-get upgrade -y >> "$LOG_FILE" 2>&1

# Install essential packages
echo "Installing essential packages..." | tee -a "$LOG_FILE"
apt-get install -y \
    curl \
    wget \
    git \
    unzip \
    software-properties-common \
    apt-transport-https \
    ca-certificates \
    gnupg \
    lsb-release \
    nginx \
    supervisor >> "$LOG_FILE" 2>&1

# Install Node.js 18.x using NodeSource repository
echo "Installing Node.js..." | tee -a "$LOG_FILE"

# Add NodeSource repository
curl -fsSL https://deb.nodesource.com/setup_18.x | bash - >> "$LOG_FILE" 2>&1

# Install Node.js
apt-get install -y nodejs >> "$LOG_FILE" 2>&1

# Verify Node.js installation
echo "Verifying Node.js installation..." | tee -a "$LOG_FILE"
node --version >> "$LOG_FILE" 2>&1
npm --version >> "$LOG_FILE" 2>&1

# Install PM2 for process management
echo "Installing PM2..." | tee -a "$LOG_FILE"
npm install -g pm2 >> "$LOG_FILE" 2>&1

# Create application directory
echo "Creating application directory..." | tee -a "$LOG_FILE"
mkdir -p "$APP_DIR"
chown -R www-data:www-data "$APP_DIR"

# Create systemd service for the application
echo "Creating systemd service..." | tee -a "$LOG_FILE"

cat > /etc/systemd/system/$APP_NAME.service << EOF
[Unit]
Description=$APP_NAME Node.js Application
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=$APP_DIR
ExecStart=/usr/bin/node server.js
Restart=always
RestartSec=10
Environment=NODE_ENV=production
Environment=PORT=3000

# Logging
StandardOutput=syslog
StandardError=syslog
SyslogIdentifier=$APP_NAME

[Install]
WantedBy=multi-user.target
EOF

# Configure Nginx as reverse proxy
echo "Configuring Nginx..." | tee -a "$LOG_FILE"
cat > /etc/nginx/sites-available/$APP_NAME << EOF
server {
    listen 80;
    server_name _;

    location / {
        proxy_pass http://localhost:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_cache_bypass \$http_upgrade;
        proxy_read_timeout 86400;
    }

    location /health {
        proxy_pass http://localhost:3000/health;
        proxy_http_version 1.1;
        proxy_set_header Host \$host;
        access_log off;
    }
}
EOF

# Enable the Nginx site
ln -sf /etc/nginx/sites-available/$APP_NAME /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Test Nginx configuration
nginx -t >> "$LOG_FILE" 2>&1

# Configure firewall
echo "Configuring firewall..." | tee -a "$LOG_FILE"
ufw --force enable >> "$LOG_FILE" 2>&1
ufw allow ssh >> "$LOG_FILE" 2>&1
ufw allow http >> "$LOG_FILE" 2>&1
ufw allow https >> "$LOG_FILE" 2>&1
ufw allow 3000 >> "$LOG_FILE" 2>&1

# Create deployment script
echo "Creating deployment script..." | tee -a "$LOG_FILE"
cat > /usr/local/bin/deploy-app.sh << 'EOF'
#!/bin/bash

APP_NAME="lightsail-demo-app"
APP_DIR="/var/www/$APP_NAME"
BACKUP_DIR="/var/backups/$APP_NAME"

# Create backup
if [ -d "$APP_DIR" ]; then
    echo "Creating backup..."
    mkdir -p $BACKUP_DIR
    cp -r $APP_DIR $BACKUP_DIR/backup-$(date +%Y%m%d_%H%M%S)
fi

# Install dependencies if package.json exists
if [ -f "$APP_DIR/package.json" ]; then
    echo "Installing dependencies..."
    cd $APP_DIR
    npm ci --production
fi

# Restart application
echo "Restarting application..."
systemctl restart $APP_NAME
systemctl enable $APP_NAME

# Restart Nginx
systemctl restart nginx
systemctl enable nginx

echo "Deployment completed successfully!"
EOF

chmod +x /usr/local/bin/deploy-app.sh

# Create a simple placeholder application
echo "Creating placeholder application..." | tee -a "$LOG_FILE"
cat > $APP_DIR/server.js << 'EOF'
const http = require('http');

const server = http.createServer((req, res) => {
    if (req.url === '/health') {
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({
            status: 'healthy',
            message: 'Lightsail instance is ready for deployment',
            timestamp: new Date().toISOString()
        }));
    } else {
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({
            message: 'Lightsail instance is ready!',
            status: 'Waiting for application deployment',
            timestamp: new Date().toISOString()
        }));
    }
});

const PORT = process.env.PORT || 3000;
server.listen(PORT, () => {
    console.log(`Placeholder server running on port $${PORT}`);
});
EOF

cat > $APP_DIR/package.json << 'EOF'
{
  "name": "placeholder-app",
  "version": "1.0.0",
  "main": "server.js",
  "scripts": {
    "start": "node server.js"
  }
}
EOF

chown -R www-data:www-data "$APP_DIR"

# Start services
echo "Starting services..." | tee -a "$LOG_FILE"
systemctl daemon-reload
systemctl enable $APP_NAME
systemctl start $APP_NAME
systemctl enable nginx
systemctl restart nginx

# Install AWS CLI for GitHub Actions integration
echo "Installing AWS CLI..." | tee -a "$LOG_FILE"
curl "https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip" -o "awscliv2.zip" >> "$LOG_FILE" 2>&1
unzip awscliv2.zip >> "$LOG_FILE" 2>&1
./aws/install >> "$LOG_FILE" 2>&1
rm -rf aws awscliv2.zip

# Create SSH directory for ubuntu user
echo "Setting up SSH for ubuntu user..." | tee -a "$LOG_FILE"
mkdir -p /home/ubuntu/.ssh
chown ubuntu:ubuntu /home/ubuntu/.ssh
chmod 700 /home/ubuntu/.ssh

echo "User data script completed successfully at $(date)" | tee -a "$LOG_FILE"
echo "Instance is ready for GitHub Actions deployment!" | tee -a "$LOG_FILE"

# Display status
echo "=== Service Status ===" | tee -a "$LOG_FILE"
systemctl status $APP_NAME --no-pager >> "$LOG_FILE" 2>&1
systemctl status nginx --no-pager >> "$LOG_FILE" 2>&1

echo "=== Network Status ===" | tee -a "$LOG_FILE"
ss -tlnp | grep :3000 >> "$LOG_FILE" 2>&1
ss -tlnp | grep :80 >> "$LOG_FILE" 2>&1

echo "Setup completed! Instance is ready for deployment." | tee -a "$LOG_FILE"

# Create completion marker for GitHub Actions to check
echo "Setup completed!" > /var/log/user-data-complete.marker
