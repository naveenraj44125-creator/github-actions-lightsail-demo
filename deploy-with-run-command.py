#!/usr/bin/env python3

"""
AWS Lightsail Deployment using Run Commands
==========================================
Deploy application to Lightsail instance using AWS run commands instead of SSH keys.
"""

import boto3
import subprocess
import tempfile
import os
import time
import sys
import json
import base64

class LightsailDeployer:
    def __init__(self, instance_name, region='us-east-1'):
        self.lightsail = boto3.client('lightsail', region_name=region)
        self.instance_name = instance_name
        self.region = region
    
    def run_command(self, command, timeout=300):
        """Execute command on Lightsail instance using get_instance_access_details"""
        try:
            print(f"🔧 Running: {command[:100]}{'...' if len(command) > 100 else ''}")
            
            # Get SSH access details
            ssh_response = self.lightsail.get_instance_access_details(instanceName=self.instance_name)
            ssh_details = ssh_response['accessDetails']
            
            # Create temporary SSH key files
            key_path, cert_path = self.create_ssh_files(ssh_details)
            
            try:
                ssh_cmd = [
                    'ssh', '-i', key_path, '-o', f'CertificateFile={cert_path}',
                    '-o', 'StrictHostKeyChecking=no', '-o', 'UserKnownHostsFile=/dev/null',
                    '-o', 'ConnectTimeout=15', '-o', 'IdentitiesOnly=yes',
                    f'{ssh_details["username"]}@{ssh_details["ipAddress"]}', command
                ]
                
                result = subprocess.run(ssh_cmd, capture_output=True, text=True, timeout=timeout)
                
                if result.returncode == 0:
                    print(f"   ✅ Success")
                    if result.stdout.strip():
                        # Limit output for readability
                        lines = result.stdout.strip().split('\n')
                        for line in lines[:20]:  # Show first 20 lines
                            print(f"   {line}")
                        if len(lines) > 20:
                            print(f"   ... ({len(lines) - 20} more lines)")
                    return True, result.stdout.strip()
                else:
                    print(f"   ❌ Failed (exit code: {result.returncode})")
                    if result.stderr.strip():
                        print(f"   Error: {result.stderr.strip()}")
                    return False, result.stderr.strip()
                
            finally:
                # Clean up temporary files
                try:
                    os.unlink(key_path)
                    os.unlink(cert_path)
                except:
                    pass
                
        except Exception as e:
            print(f"   ❌ Error: {str(e)}")
            return False, str(e)

    def create_ssh_files(self, ssh_details):
        """Create temporary SSH key files"""
        with tempfile.NamedTemporaryFile(mode='w', suffix='.pem', delete=False) as key_file:
            key_file.write(ssh_details['privateKey'])
            key_path = key_file.name
        
        cert_path = key_path + '-cert.pub'
        cert_parts = ssh_details['certKey'].split(' ', 2)
        formatted_cert = f'{cert_parts[0]} {cert_parts[1]}\n' if len(cert_parts) >= 2 else ssh_details['certKey'] + '\n'
        
        with open(cert_path, 'w') as cert_file:
            cert_file.write(formatted_cert)
        
        os.chmod(key_path, 0o600)
        os.chmod(cert_path, 0o600)
        
        return key_path, cert_path

    def copy_file_to_instance(self, local_path, remote_path):
        """Copy file to instance using SCP"""
        try:
            print(f"📤 Copying {local_path} to {remote_path}")
            
            ssh_response = self.lightsail.get_instance_access_details(instanceName=self.instance_name)
            ssh_details = ssh_response['accessDetails']
            
            key_path, cert_path = self.create_ssh_files(ssh_details)
            
            try:
                scp_cmd = [
                    'scp', '-i', key_path, '-o', f'CertificateFile={cert_path}',
                    '-o', 'StrictHostKeyChecking=no', '-o', 'UserKnownHostsFile=/dev/null',
                    '-o', 'ConnectTimeout=15', '-o', 'IdentitiesOnly=yes',
                    local_path, f'{ssh_details["username"]}@{ssh_details["ipAddress"]}:{remote_path}'
                ]
                
                result = subprocess.run(scp_cmd, capture_output=True, text=True, timeout=300)
                
                if result.returncode == 0:
                    print(f"   ✅ File copied successfully")
                    return True
                else:
                    print(f"   ❌ Failed to copy file (exit code: {result.returncode})")
                    if result.stderr.strip():
                        print(f"   Error: {result.stderr.strip()}")
                    return False
                
            finally:
                try:
                    os.unlink(key_path)
                    os.unlink(cert_path)
                except:
                    pass
                
        except Exception as e:
            print(f"   ❌ Error copying file: {str(e)}")
            return False

    def deploy_application(self, app_archive_path, env_vars=None):
        """Deploy application to Lightsail instance"""
        print("🚀 Starting application deployment...")
        
        # Copy application archive to instance
        if not self.copy_file_to_instance(app_archive_path, '/tmp/app.tar.gz'):
            return False
        
        # Stop existing application
        print("🛑 Stopping existing application...")
        self.run_command("sudo systemctl stop lightsail-demo-app || true")
        
        # Extract and deploy application
        deployment_script = f'''
set -e

# Extract application
cd /tmp
tar -xzf app.tar.gz

# Ensure directory structure exists
echo "Creating /var/www directory..."
sudo mkdir -p /var/www
sudo chown root:root /var/www
sudo chmod 755 /var/www

# Backup current version if it exists
if [ -d "/var/www/lightsail-demo-app" ]; then
    echo "Backing up existing application..."
    sudo cp -r /var/www/lightsail-demo-app /var/www/lightsail-demo-app.backup.$(date +%Y%m%d_%H%M%S)
fi

# Deploy new version
echo "Deploying new version..."
sudo rm -rf /var/www/lightsail-demo-app
sudo mv /tmp/app /var/www/lightsail-demo-app
sudo chown -R www-data:www-data /var/www/lightsail-demo-app

# Install dependencies
cd /var/www/lightsail-demo-app

# Find Node.js and npm paths
NODE_PATH=$(which node || echo "")
NPM_PATH=$(which npm || echo "")

if [ -z "$NODE_PATH" ]; then
    if [ -f "/usr/bin/node" ]; then
        NODE_PATH="/usr/bin/node"
    elif [ -f "/snap/bin/node" ]; then
        NODE_PATH="/snap/bin/node"
    else
        echo "ERROR: Node.js not found"
        exit 1
    fi
fi

if [ -z "$NPM_PATH" ]; then
    if [ -f "/usr/bin/npm" ]; then
        NPM_PATH="/usr/bin/npm"
    elif [ -f "/snap/bin/npm" ]; then
        NPM_PATH="/snap/bin/npm"
    else
        echo "ERROR: npm not found"
        exit 1
    fi
fi

echo "Using Node.js at: $NODE_PATH"
echo "Using npm at: $NPM_PATH"

# Fix npm cache permissions and install dependencies
sudo mkdir -p /var/www/.npm
sudo chown -R www-data:www-data /var/www/.npm
sudo chmod -R 755 /var/www/.npm

# Install dependencies
sudo -u www-data env PATH="/usr/bin:/usr/local/bin:/bin:/sbin:/snap/bin" HOME="/var/www" "$NPM_PATH" ci --production --cache /var/www/.npm
'''
        
        success, output = self.run_command(deployment_script, timeout=600)
        if not success:
            print("❌ Failed to deploy application")
            return False
        
        # Create environment file
        if env_vars:
            print("📝 Creating environment file...")
            env_content = "\\n".join([f"{k}={v}" for k, v in env_vars.items()])
            env_script = f'''
sudo tee /var/www/lightsail-demo-app/.env > /dev/null << 'ENVEOF'
{env_content}
ENVEOF
'''
            self.run_command(env_script)
        
        # Update systemd service
        print("⚙️ Updating systemd service...")
        service_script = '''
# Get Node.js path
NODE_PATH=$(which node || echo "")
if [ -z "$NODE_PATH" ]; then
    if [ -f "/usr/bin/node" ]; then
        NODE_PATH="/usr/bin/node"
    elif [ -f "/snap/bin/node" ]; then
        NODE_PATH="/snap/bin/node"
    fi
fi

sudo tee /etc/systemd/system/lightsail-demo-app.service > /dev/null << SERVICEEOF
[Unit]
Description=Lightsail Demo App Node.js Application
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/lightsail-demo-app
ExecStart=$NODE_PATH server.js
Restart=always
RestartSec=10
EnvironmentFile=/var/www/lightsail-demo-app/.env

# Logging
StandardOutput=syslog
StandardError=syslog
SyslogIdentifier=lightsail-demo-app

[Install]
WantedBy=multi-user.target
SERVICEEOF
'''
        
        self.run_command(service_script)
        
        # Start application
        print("🚀 Starting application...")
        self.run_command("sudo systemctl daemon-reload")
        self.run_command("sudo systemctl enable lightsail-demo-app")
        self.run_command("sudo systemctl start lightsail-demo-app")
        self.run_command("sudo systemctl restart nginx")
        
        # Wait and check status
        time.sleep(5)
        success, output = self.run_command("sudo systemctl status lightsail-demo-app --no-pager")
        
        print("✅ Application deployment completed!")
        return True

def main():
    if len(sys.argv) < 3:
        print("Usage: python3 deploy-with-run-command.py <instance_name> <app_archive_path> [env_vars_json]")
        sys.exit(1)
    
    instance_name = sys.argv[1]
    app_archive_path = sys.argv[2]
    env_vars = {}
    
    if len(sys.argv) > 3:
        try:
            env_vars = json.loads(sys.argv[3])
        except json.JSONDecodeError:
            print("Error: Invalid JSON for environment variables")
            sys.exit(1)
    
    deployer = LightsailDeployer(instance_name)
    
    if deployer.deploy_application(app_archive_path, env_vars):
        print("🎉 Deployment successful!")
        sys.exit(0)
    else:
        print("❌ Deployment failed!")
        sys.exit(1)

if __name__ == "__main__":
    main()
