# Lightsail QBR Application - Deployment Guide

This guide will walk you through deploying the Lightsail Quarterly Business Review (QBR) application on AWS Lightsail using a LAMP stack.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [AWS Lightsail Setup](#aws-lightsail-setup)
3. [LAMP Stack Configuration](#lamp-stack-configuration)
4. [Database Setup](#database-setup)
5. [Application Deployment](#application-deployment)
6. [SSL Certificate Setup](#ssl-certificate-setup)
7. [Security Configuration](#security-configuration)
8. [Testing and Verification](#testing-and-verification)
9. [Maintenance and Updates](#maintenance-and-updates)
10. [Troubleshooting](#troubleshooting)

## Prerequisites

Before starting, ensure you have:

- AWS account with appropriate permissions
- Basic knowledge of Linux command line
- SSH client installed on your local machine
- Domain name (optional, for SSL setup)

## AWS Lightsail Setup

### 1. Create a Lightsail Instance

1. **Log in to AWS Lightsail Console**
   - Navigate to [AWS Lightsail Console](https://lightsail.aws.amazon.com/)
   - Click "Create instance"

2. **Choose Instance Configuration**
   - **Platform**: Linux/Unix
   - **Blueprint**: LAMP (PHP 8)
   - **Instance plan**: $10 USD/month (2 GB RAM, 1 vCPU, 60 GB SSD) - Recommended minimum
   - **Instance name**: `lightsail-qbr-app`
   - **Key pair**: Create new or use existing

3. **Configure Networking**
   - **Static IP**: Attach a static IP address
   - **Firewall**: Enable HTTP (80) and HTTPS (443)

4. **Launch Instance**
   - Click "Create instance"
   - Wait for the instance to be in "Running" state

### 2. Connect to Your Instance

```bash
# Using Lightsail browser-based SSH (recommended for beginners)
# Or connect via SSH client:
ssh -i /path/to/your-key.pem bitnami@YOUR_STATIC_IP
```

## LAMP Stack Configuration

### 1. Update System Packages

```bash
sudo apt update && sudo apt upgrade -y
```

### 2. Verify LAMP Stack Components

```bash
# Check Apache
sudo systemctl status apache2

# Check MySQL
sudo systemctl status mysql

# Check PHP
php -v
```

### 3. Configure Apache Virtual Host

```bash
# Create application directory
sudo mkdir -p /opt/bitnami/apache/htdocs/qbr-app

# Create virtual host configuration
sudo nano /opt/bitnami/apache/conf/vhosts/qbr-app.conf
```

Add the following configuration:

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    ServerAlias www.your-domain.com
    DocumentRoot /opt/bitnami/apache/htdocs/qbr-app
    
    <Directory /opt/bitnami/apache/htdocs/qbr-app>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog /opt/bitnami/apache/logs/qbr-app_error.log
    CustomLog /opt/bitnami/apache/logs/qbr-app_access.log combined
</VirtualHost>
```

### 4. Enable Required PHP Extensions

```bash
# Check installed PHP extensions
php -m

# Install additional extensions if needed
sudo apt install php-mysqli php-pdo php-pdo-mysql php-json php-mbstring -y

# Restart Apache
sudo /opt/bitnami/ctlscript.sh restart apache
```

## Database Setup

### 1. Secure MySQL Installation

```bash
# Run MySQL secure installation
sudo mysql_secure_installation
```

Follow the prompts:
- Set root password: **Yes** (choose a strong password)
- Remove anonymous users: **Yes**
- Disallow root login remotely: **Yes**
- Remove test database: **Yes**
- Reload privilege tables: **Yes**

### 2. Create Application Database

```bash
# Connect to MySQL as root
sudo mysql -u root -p
```

Execute the following SQL commands:

```sql
-- Create database
CREATE DATABASE lightsail_qbr CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create application user
CREATE USER 'qbr_user'@'localhost' IDENTIFIED BY 'qbr_password_2025';

-- Grant privileges
GRANT ALL PRIVILEGES ON lightsail_qbr.* TO 'qbr_user'@'localhost';

-- Flush privileges
FLUSH PRIVILEGES;

-- Exit MySQL
EXIT;
```

### 3. Import Database Schema

```bash
# Navigate to application directory
cd /opt/bitnami/apache/htdocs/qbr-app

# Import the database schema
mysql -u qbr_user -p lightsail_qbr < setup-database.sql
```

## Application Deployment

### 1. Upload Application Files

**Option A: Using SCP (if you have the files locally)**

```bash
# From your local machine
scp -i /path/to/your-key.pem -r lightsail-qbr-app/* bitnami@YOUR_STATIC_IP:/tmp/

# On the server
sudo mv /tmp/* /opt/bitnami/apache/htdocs/qbr-app/
```

**Option B: Using Git (recommended)**

```bash
# Install Git if not available
sudo apt install git -y

# Clone or download your application
cd /opt/bitnami/apache/htdocs/
sudo git clone YOUR_REPOSITORY_URL qbr-app

# Or create files manually (copy the content from your local files)
```

### 2. Set Proper Permissions

```bash
# Set ownership
sudo chown -R bitnami:daemon /opt/bitnami/apache/htdocs/qbr-app

# Set permissions
sudo chmod -R 755 /opt/bitnami/apache/htdocs/qbr-app
sudo chmod -R 644 /opt/bitnami/apache/htdocs/qbr-app/*.php
sudo chmod -R 644 /opt/bitnami/apache/htdocs/qbr-app/assets/css/*
sudo chmod -R 644 /opt/bitnami/apache/htdocs/qbr-app/assets/js/*
```

### 3. Configure Database Connection

```bash
# Edit database configuration
sudo nano /opt/bitnami/apache/htdocs/qbr-app/config/database.php
```

Verify the database credentials match what you created:

```php
$host = 'localhost';
$dbname = 'lightsail_qbr';
$username = 'qbr_user';
$password = 'qbr_password_2025';
```

### 4. Test Database Connection

```bash
# Create a simple test script
sudo nano /opt/bitnami/apache/htdocs/qbr-app/test-db.php
```

Add the following content:

```php
<?php
require_once 'config/database.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    echo "Database connection successful!";
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage();
}
?>
```

Visit `http://YOUR_STATIC_IP/test-db.php` to verify the connection.

### 5. Configure Apache

```bash
# Enable mod_rewrite
sudo a2enmod rewrite

# Restart Apache
sudo /opt/bitnami/ctlscript.sh restart apache
```

## SSL Certificate Setup (Optional but Recommended)

### 1. Using Let's Encrypt with Certbot

```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache -y

# Obtain SSL certificate
sudo certbot --apache -d your-domain.com -d www.your-domain.com
```

### 2. Configure Auto-Renewal

```bash
# Test renewal
sudo certbot renew --dry-run

# Add to crontab for auto-renewal
sudo crontab -e

# Add this line:
0 12 * * * /usr/bin/certbot renew --quiet
```

## Security Configuration

### 1. Configure Firewall

```bash
# Check current firewall status
sudo ufw status

# Allow necessary ports
sudo ufw allow 22/tcp    # SSH
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS

# Enable firewall
sudo ufw enable
```

### 2. Secure PHP Configuration

```bash
# Edit PHP configuration
sudo nano /opt/bitnami/php/etc/php.ini
```

Update these settings:

```ini
expose_php = Off
display_errors = Off
log_errors = On
error_log = /opt/bitnami/apache/logs/php_errors.log
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
```

### 3. Secure Apache Configuration

```bash
# Edit Apache security configuration
sudo nano /opt/bitnami/apache/conf/httpd.conf
```

Add these security headers:

```apache
# Security Headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
Header always set Strict-Transport-Security "max-age=63072000; includeSubDomains; preload"
Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self';"

# Hide Apache version
ServerTokens Prod
ServerSignature Off
```

### 4. Remove Test Files

```bash
# Remove database test file
sudo rm /opt/bitnami/apache/htdocs/qbr-app/test-db.php

# Remove default Bitnami pages (optional)
sudo mv /opt/bitnami/apache/htdocs/index.html /opt/bitnami/apache/htdocs/index.html.backup
```

## Testing and Verification

### 1. Application Access Test

1. **Open your browser** and navigate to:
   - `http://YOUR_STATIC_IP` or `https://your-domain.com`

2. **Test Login Functionality**:
   - Admin login: `admin` / `admin123`
   - Employee login: `employee` / `employee123`

3. **Test Core Features**:
   - Project listing on dashboard
   - Voting functionality
   - Comment system
   - Admin panel access (admin users only)

### 2. Performance Test

```bash
# Install Apache Bench for testing
sudo apt install apache2-utils -y

# Test application performance
ab -n 100 -c 10 http://YOUR_STATIC_IP/
```

### 3. Security Test

```bash
# Check for common vulnerabilities
sudo apt install nikto -y
nikto -h http://YOUR_STATIC_IP
```

## Maintenance and Updates

### 1. Regular Backups

```bash
# Create backup script
sudo nano /home/bitnami/backup-qbr.sh
```

Add the following content:

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/home/bitnami/backups"

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u qbr_user -p'qbr_password_2025' lightsail_qbr > $BACKUP_DIR/qbr_db_$DATE.sql

# Backup application files
tar -czf $BACKUP_DIR/qbr_app_$DATE.tar.gz -C /opt/bitnami/apache/htdocs qbr-app

# Keep only last 7 days of backups
find $BACKUP_DIR -name "qbr_*" -mtime +7 -delete

echo "Backup completed: $DATE"
```

```bash
# Make script executable
sudo chmod +x /home/bitnami/backup-qbr.sh

# Add to crontab for daily backups
crontab -e

# Add this line for daily backup at 2 AM:
0 2 * * * /home/bitnami/backup-qbr.sh
```

### 2. System Updates

```bash
# Create update script
sudo nano /home/bitnami/update-system.sh
```

Add the following content:

```bash
#!/bin/bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Restart services
sudo /opt/bitnami/ctlscript.sh restart

echo "System update completed: $(date)"
```

### 3. Log Monitoring

```bash
# Monitor Apache logs
sudo tail -f /opt/bitnami/apache/logs/qbr-app_access.log
sudo tail -f /opt/bitnami/apache/logs/qbr-app_error.log

# Monitor MySQL logs
sudo tail -f /var/log/mysql/error.log
```

## Troubleshooting

### Common Issues and Solutions

#### 1. Database Connection Issues

**Problem**: "Database connection failed"

**Solutions**:
```bash
# Check MySQL service
sudo systemctl status mysql

# Restart MySQL
sudo systemctl restart mysql

# Verify database credentials
mysql -u qbr_user -p lightsail_qbr
```

#### 2. Permission Issues

**Problem**: "Permission denied" errors

**Solutions**:
```bash
# Fix file permissions
sudo chown -R bitnami:daemon /opt/bitnami/apache/htdocs/qbr-app
sudo chmod -R 755 /opt/bitnami/apache/htdocs/qbr-app
```

#### 3. Apache Not Starting

**Problem**: Apache fails to start

**Solutions**:
```bash
# Check Apache configuration
sudo /opt/bitnami/apache/bin/httpd -t

# Check Apache logs
sudo tail -f /opt/bitnami/apache/logs/error_log

# Restart Apache
sudo /opt/bitnami/ctlscript.sh restart apache
```

#### 4. PHP Errors

**Problem**: PHP errors or blank pages

**Solutions**:
```bash
# Enable PHP error display (temporarily)
sudo nano /opt/bitnami/php/etc/php.ini
# Set: display_errors = On

# Check PHP logs
sudo tail -f /opt/bitnami/apache/logs/php_errors.log
```

#### 5. SSL Certificate Issues

**Problem**: SSL certificate not working

**Solutions**:
```bash
# Check certificate status
sudo certbot certificates

# Renew certificate
sudo certbot renew

# Check Apache SSL configuration
sudo nano /opt/bitnami/apache/conf/bitnami/bitnami-ssl.conf
```

### Performance Optimization

#### 1. Enable PHP OPcache

```bash
# Edit PHP configuration
sudo nano /opt/bitnami/php/etc/php.ini

# Add or uncomment:
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
```

#### 2. Configure MySQL for Better Performance

```bash
# Edit MySQL configuration
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf

# Add under [mysqld] section:
innodb_buffer_pool_size = 512M
query_cache_type = 1
query_cache_size = 64M
```

#### 3. Enable Apache Compression

```bash
# Enable mod_deflate
sudo a2enmod deflate

# Add to virtual host configuration:
<Location />
    SetOutputFilter DEFLATE
    SetEnvIfNoCase Request_URI \
        \.(?:gif|jpe?g|png)$ no-gzip dont-vary
    SetEnvIfNoCase Request_URI \
        \.(?:exe|t?gz|zip|bz2|sit|rar)$ no-gzip dont-vary
</Location>
```

## Support and Resources

- **AWS Lightsail Documentation**: https://docs.aws.amazon.com/lightsail/
- **Bitnami LAMP Stack Guide**: https://docs.bitnami.com/aws/infrastructure/lamp/
- **PHP Documentation**: https://www.php.net/docs.php
- **MySQL Documentation**: https://dev.mysql.com/doc/

## Security Best Practices

1. **Regular Updates**: Keep all system packages and applications updated
2. **Strong Passwords**: Use complex passwords for all accounts
3. **Backup Strategy**: Implement regular automated backups
4. **Monitoring**: Set up log monitoring and alerting
5. **Access Control**: Limit SSH access and use key-based authentication
6. **SSL/TLS**: Always use HTTPS in production
7. **Database Security**: Regularly review database user permissions
8. **File Permissions**: Maintain proper file and directory permissions

---

**Congratulations!** Your Lightsail QBR application should now be successfully deployed and running on AWS Lightsail with a secure LAMP stack configuration.

For additional support or questions, please refer to the troubleshooting section or consult the AWS Lightsail documentation.
