#!/bin/bash

# User data script to set up the Lightsail instance for Node.js application deployment
# This script runs when the instance first boots up
# Updated: Force instance recreation with enhanced Node.js installation

set -e

# Variables
APP_NAME="${app_name}"
APP_DIR="/var/www/$APP_NAME"
LOG_FILE="/var/log/user-data.log"

# Redirect output to log file
exec > >(tee -a "$LOG_FILE") 2>&1

echo "Starting user data script at $(date)"

# Update system packages
echo "Updating system packages..."
apt-get update -y
apt-get upgrade -y

# Install essential packages
echo "Installing essential packages..."
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
    supervisor

# Install Node.js 18.x with multiple fallback methods
echo "Installing Node.js..."

NODE_INSTALLED=false

# Method 1: Try NodeSource repository
echo "Attempting NodeSource installation..."
if curl -fsSL https://deb.nodesource.com/setup_18.x | bash -; then
    echo "NodeSource repository added successfully"
    if apt-get update && apt-get install -y nodejs; then
        echo "Node.js installed successfully via NodeSource"
        NODE_INSTALLED=true
    else
        echo "Failed to install Node.js via NodeSource"
    fi
else
    echo "Failed to add NodeSource repository"
fi

# Method 2: Try snap installation if NodeSource failed
if [ "$NODE_INSTALLED" = false ]; then
    echo "Attempting snap installation..."
    if snap install node --classic; then
        echo "Node.js installed successfully via snap"
        # Create symlinks for snap installation
        ln -sf /snap/bin/node /usr/local/bin/node
        ln -sf /snap/bin/npm /usr/local/bin/npm
        NODE_INSTALLED=true
    else
        echo "Failed to install Node.js via snap"
    fi
fi

# Method 3: Try Ubuntu default repository as last resort
if [ "$NODE_INSTALLED" = false ]; then
    echo "Attempting Ubuntu repository installation..."
    if apt-get install -y nodejs npm; then
        echo "Node.js installed successfully via Ubuntu repository"
        NODE_INSTALLED=true
    else
        echo "Failed to install Node.js via Ubuntu repository"
    fi
fi

# Method 4: Try manual binary installation if all else fails
if [ "$NODE_INSTALLED" = false ]; then
    echo "Attempting manual binary installation..."
    NODE_VERSION="18.20.4"
    cd /tmp
    if wget "https://nodejs.org/dist/v$NODE_VERSION/node-v$NODE_VERSION-linux-x64.tar.xz"; then
        tar -xf "node-v$NODE_VERSION-linux-x64.tar.xz"
        cp -r "node-v$NODE_VERSION-linux-x64"/* /usr/local/
        rm -rf "node-v$NODE_VERSION-linux-x64"*
        echo "Node.js installed successfully via manual binary"
        NODE_INSTALLED=true
    else
        echo "Failed to download Node.js binary"
    fi
fi

# Verify Node.js installation
if [ "$NODE_INSTALLED" = true ]; then
    # Update PATH to include all possible Node.js locations
    export PATH="/usr/local/bin:/snap/bin:/usr/bin:$PATH"
    
    # Test Node.js installation
    if command -v node >/dev/null 2>&1 && command -v npm >/dev/null 2>&1; then
        echo "Node.js version: $(node --version)"
        echo "npm version: $(npm --version)"
        echo "Node.js installation verified successfully!"
    else
        echo "ERROR: Node.js installation verification failed"
        exit 1
    fi
else
    echo "ERROR: All Node.js installation methods failed"
    exit 1
fi

# Install PM2 for process management
echo "Installing PM2..."
npm install -g pm2

# Create application directory
echo "Creating application directory..."
mkdir -p $APP_DIR
chown -R www-data:www-data $APP_DIR

# Create systemd service for the application
echo "Creating systemd service..."

# Determine Node.js path
NODE_PATH=""
if command -v /usr/local/bin/node >/dev/null 2>&1; then
    NODE_PATH="/usr/local/bin/node"
elif command -v /snap/bin/node >/dev/null 2>&1; then
    NODE_PATH="/snap/bin/node"
elif command -v /usr/bin/node >/dev/null 2>&1; then
    NODE_PATH="/usr/bin/node"
else
    NODE_PATH="$(which node)"
fi

echo "Using Node.js path for systemd service: $NODE_PATH"

cat > /etc/systemd/system/$APP_NAME.service << EOF
[Unit]
Description=$APP_NAME Node.js Application
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=$APP_DIR
ExecStart=$NODE_PATH server.js
Restart=always
RestartSec=10
Environment=NODE_ENV=production
Environment=PORT=3000
Environment=PATH=/usr/local/bin:/snap/bin:/usr/bin:/bin

# Logging
StandardOutput=syslog
StandardError=syslog
SyslogIdentifier=$APP_NAME

[Install]
WantedBy=multi-user.target
EOF

# Configure Nginx as reverse proxy
echo "Configuring Nginx..."
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
nginx -t

# Configure firewall
echo "Configuring firewall..."
ufw --force enable
ufw allow ssh
ufw allow http
ufw allow https
ufw allow 3000

# Create deployment script
echo "Creating deployment script..."
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
echo "Creating placeholder application..."
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
    console.log(\`Placeholder server running on port $${PORT}\`);
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

chown -R www-data:www-data $APP_DIR

# Start services
echo "Starting services..."
systemctl daemon-reload
systemctl enable $APP_NAME
systemctl start $APP_NAME
systemctl enable nginx
systemctl restart nginx

# Install AWS CLI for GitHub Actions integration
echo "Installing AWS CLI..."
curl "https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip" -o "awscliv2.zip"
unzip awscliv2.zip
./aws/install
rm -rf aws awscliv2.zip

# Create SSH directory for ubuntu user
echo "Setting up SSH for ubuntu user..."
mkdir -p /home/ubuntu/.ssh
chown ubuntu:ubuntu /home/ubuntu/.ssh
chmod 700 /home/ubuntu/.ssh

# Set up log rotation
echo "Setting up log rotation..."
cat > /etc/logrotate.d/$APP_NAME << EOF
/var/log/$APP_NAME.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        systemctl reload $APP_NAME
    endscript
}
EOF

echo "User data script completed successfully at $(date)"
echo "Instance is ready for GitHub Actions deployment!"

# Display status
echo "=== Service Status ==="
systemctl status $APP_NAME --no-pager
systemctl status nginx --no-pager

echo "=== Network Status ==="
ss -tlnp | grep :3000
ss -tlnp | grep :80

echo "Setup completed! Instance is ready for deployment."

# Create completion marker for GitHub Actions to check
echo "Setup completed!" > /var/log/user-data-complete.marker
