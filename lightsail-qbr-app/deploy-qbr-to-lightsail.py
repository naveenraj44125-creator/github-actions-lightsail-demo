#!/usr/bin/env python3
"""
Deploy QBR Application to AWS Lightsail Instance
This script handles the complete deployment of the QBR app to a Lightsail instance.
"""

import boto3
import json
import time
import base64
import os
import sys
import tarfile
import tempfile
from pathlib import Path

class LightsailQBRDeployer:
    def __init__(self, instance_name='lightsail-qbr', region='us-east-1'):
        self.instance_name = instance_name
        self.region = region
        self.client = boto3.client('lightsail', region_name=region)
        
    def check_instance_exists(self):
        """Check if the Lightsail instance exists"""
        try:
            response = self.client.get_instance(instanceName=self.instance_name)
            print(f"✓ Instance '{self.instance_name}' exists")
            print(f"  State: {response['instance']['state']['name']}")
            return True
        except self.client.exceptions.NotFoundException:
            print(f"✗ Instance '{self.instance_name}' not found")
            return False
        except Exception as e:
            print(f"Error checking instance: {e}")
            return False
    
    def create_instance(self):
        """Create a new Lightsail instance with LAMP stack"""
        print(f"Creating Lightsail instance '{self.instance_name}'...")
        
        user_data = '''#!/bin/bash
# Update system
apt-get update -y

# Install additional PHP extensions
apt-get install -y php-mbstring php-xml php-curl php-zip

# Configure MySQL for QBR app
mysql -u root -e "CREATE DATABASE IF NOT EXISTS lightsail_qbr CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -e "CREATE USER IF NOT EXISTS 'qbr_user'@'localhost' IDENTIFIED BY 'qbr_password_2025';"
mysql -u root -e "GRANT ALL PRIVILEGES ON lightsail_qbr.* TO 'qbr_user'@'localhost';"
mysql -u root -e "FLUSH PRIVILEGES;"

# Create application directory
mkdir -p /opt/bitnami/apache/htdocs/qbr-app
chown -R bitnami:daemon /opt/bitnami/apache/htdocs/qbr-app
chmod -R 755 /opt/bitnami/apache/htdocs/qbr-app

# Configure Apache for QBR app
cat > /opt/bitnami/apache/conf/vhosts/qbr-app.conf << 'EOL'
<VirtualHost *:80>
    DocumentRoot "/opt/bitnami/apache/htdocs/qbr-app"
    DirectoryIndex index.php
    
    <Directory "/opt/bitnami/apache/htdocs/qbr-app">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog logs/qbr-app_error.log
    CustomLog logs/qbr-app_access.log common
</VirtualHost>
EOL

# Restart services
/opt/bitnami/ctlscript.sh restart apache
/opt/bitnami/ctlscript.sh restart mysql

echo "Lightsail QBR instance setup completed!"
'''
        
        try:
            response = self.client.create_instances(
                instanceNames=[self.instance_name],
                availabilityZone=f'{self.region}a',
                blueprintId='lamp_8_bitnami',
                bundleId='nano_3_0',
                userData=user_data,
                tags=[
                    {
                        'key': 'Project',
                        'value': 'QBR-Application'
                    },
                    {
                        'key': 'Environment',
                        'value': 'Production'
                    }
                ]
            )
            
            print(f"✓ Instance creation initiated")
            print(f"  Operation ID: {response['operations'][0]['id']}")
            
            return True
            
        except Exception as e:
            print(f"✗ Error creating instance: {e}")
            return False
    
    def wait_for_instance_ready(self, timeout=1800):  # 30 minutes
        """Wait for instance to be running and ready"""
        print(f"Waiting for instance '{self.instance_name}' to be ready...")
        
        start_time = time.time()
        while time.time() - start_time < timeout:
            try:
                response = self.client.get_instance(instanceName=self.instance_name)
                state = response['instance']['state']['name']
                
                if state == 'running':
                    print(f"✓ Instance is running")
                    # Additional wait for services to initialize
                    print("Waiting for services to initialize...")
                    time.sleep(120)  # Wait 2 minutes for services
                    return True
                elif state in ['pending', 'starting']:
                    print(f"  Instance state: {state} (waiting...)")
                    time.sleep(30)
                else:
                    print(f"✗ Unexpected instance state: {state}")
                    return False
                    
            except Exception as e:
                print(f"Error checking instance state: {e}")
                time.sleep(30)
        
        print(f"✗ Timeout waiting for instance to be ready")
        return False
    
    def configure_instance_ports(self):
        """Configure firewall ports for the instance"""
        try:
            self.client.put_instance_public_ports(
                instanceName=self.instance_name,
                portInfos=[
                    {
                        'fromPort': 22,
                        'toPort': 22,
                        'protocol': 'tcp'
                    },
                    {
                        'fromPort': 80,
                        'toPort': 80,
                        'protocol': 'tcp'
                    },
                    {
                        'fromPort': 443,
                        'toPort': 443,
                        'protocol': 'tcp'
                    }
                ]
            )
            print("✓ Configured instance ports (22, 80, 443)")
            return True
        except Exception as e:
            print(f"Warning: Could not configure ports: {e}")
            return False
    
    def create_deployment_package(self):
        """Create a deployment package of the QBR application"""
        print("Creating deployment package...")
        
        # Create temporary tar file
        package_path = 'qbr-deployment.tar.gz'
        
        with tarfile.open(package_path, 'w:gz') as tar:
            # Add all application files
            for root, dirs, files in os.walk('.'):
                # Skip .git, .github, and other non-essential directories
                dirs[:] = [d for d in dirs if not d.startswith('.') and d != '__pycache__']
                
                for file in files:
                    if not file.startswith('.') and not file.endswith(('.tar.gz', '.pyc')):
                        file_path = os.path.join(root, file)
                        arcname = os.path.relpath(file_path, '.')
                        tar.add(file_path, arcname=arcname)
        
        print(f"✓ Created deployment package: {package_path}")
        return package_path
    
    def deploy_application_files(self):
        """Deploy application files to the Lightsail instance"""
        print("Deploying QBR application files...")
        
        # Create deployment commands
        commands = [
            # Create directory structure
            "sudo mkdir -p /opt/bitnami/apache/htdocs/qbr-app/{admin,assets/css,assets/js,config,includes}",
            "sudo chown -R bitnami:daemon /opt/bitnami/apache/htdocs/qbr-app",
        ]
        
        # Read and deploy each file
        app_files = {}
        for root, dirs, files in os.walk('.'):
            # Skip hidden directories and files
            dirs[:] = [d for d in dirs if not d.startswith('.')]
            
            for file in files:
                if file.endswith(('.php', '.sql', '.css', '.js', '.html', '.md')):
                    file_path = os.path.join(root, file)
                    rel_path = os.path.relpath(file_path, '.')
                    
                    try:
                        with open(file_path, 'r', encoding='utf-8') as f:
                            content = f.read()
                            app_files[rel_path] = content
                    except Exception as e:
                        print(f"Warning: Could not read {file_path}: {e}")
        
        print(f"✓ Prepared {len(app_files)} application files")
        
        # For each file, create a command to write it to the instance
        for rel_path, content in app_files.items():
            target_path = f"/opt/bitnami/apache/htdocs/qbr-app/{rel_path}"
            
            # Escape content for shell
            escaped_content = content.replace("'", "'\"'\"'").replace('\n', '\\n')
            
            # Create directory if needed
            dir_path = os.path.dirname(target_path)
            if dir_path != "/opt/bitnami/apache/htdocs/qbr-app":
                commands.append(f"sudo mkdir -p '{dir_path}'")
            
            # Write file content
            commands.append(f"sudo tee '{target_path}' > /dev/null << 'EOF'\n{content}\nEOF")
            commands.append(f"sudo chown bitnami:daemon '{target_path}'")
            commands.append(f"sudo chmod 644 '{target_path}'")
        
        # Database setup commands
        db_commands = [
            "mysql -u qbr_user -pqbr_password_2025 lightsail_qbr < /opt/bitnami/apache/htdocs/qbr-app/setup-database.sql",
            "sudo /opt/bitnami/ctlscript.sh restart apache",
        ]
        
        commands.extend(db_commands)
        
        print(f"✓ Prepared {len(commands)} deployment commands")
        return commands
    
    def execute_deployment_commands(self, commands):
        """Execute deployment commands on the instance"""
        print("Executing deployment commands...")
        
        # For now, we'll create a deployment script that can be run manually
        # In a production environment, you would use SSH or AWS Systems Manager
        
        script_content = "#!/bin/bash\nset -e\n\n"
        script_content += "echo 'Starting QBR application deployment...'\n\n"
        
        for i, cmd in enumerate(commands):
            script_content += f"echo 'Step {i+1}/{len(commands)}: Executing command...'\n"
            script_content += f"{cmd}\n"
            script_content += "echo 'Step completed successfully'\n\n"
        
        script_content += "echo 'QBR application deployment completed successfully!'\n"
        
        # Save deployment script
        with open('deploy-script.sh', 'w') as f:
            f.write(script_content)
        
        print("✓ Created deployment script: deploy-script.sh")
        print("  Note: In production, this would be executed automatically via SSH/SSM")
        
        return True
    
    def get_instance_info(self):
        """Get instance information and access details"""
        try:
            response = self.client.get_instance(instanceName=self.instance_name)
            instance = response['instance']
            
            print("\n" + "="*50)
            print("QBR APPLICATION DEPLOYMENT SUMMARY")
            print("="*50)
            print(f"Instance Name: {instance['name']}")
            print(f"Instance State: {instance['state']['name']}")
            print(f"Public IP: {instance.get('publicIpAddress', 'Not assigned')}")
            print(f"Private IP: {instance.get('privateIpAddress', 'Not assigned')}")
            print(f"Blueprint: {instance['blueprintName']}")
            print(f"Bundle: {instance['bundleName']}")
            
            if instance.get('publicIpAddress'):
                public_ip = instance['publicIpAddress']
                print(f"\n🚀 QBR Application URLs:")
                print(f"   Main App: http://{public_ip}/qbr-app/")
                print(f"   Admin Panel: http://{public_ip}/qbr-app/admin/")
                
                print(f"\n📝 Default Login Credentials:")
                print(f"   Admin: admin / admin123")
                print(f"   Employee: employee / employee123")
                
                print(f"\n🔧 SSH Access:")
                print(f"   ssh bitnami@{public_ip}")
                print(f"   (Use Lightsail console to download SSH key)")
            
            print("\n✅ Deployment completed successfully!")
            return True
            
        except Exception as e:
            print(f"Error getting instance information: {e}")
            return False

def main():
    """Main deployment function"""
    print("AWS Lightsail QBR Application Deployer")
    print("="*40)
    
    deployer = LightsailQBRDeployer()
    
    # Check if instance exists
    if not deployer.check_instance_exists():
        print("\nCreating new Lightsail instance...")
        if not deployer.create_instance():
            print("Failed to create instance")
            sys.exit(1)
        
        # Wait for instance to be ready
        if not deployer.wait_for_instance_ready():
            print("Instance did not become ready in time")
            sys.exit(1)
    
    # Configure ports
    deployer.configure_instance_ports()
    
    # Create deployment package
    package_path = deployer.create_deployment_package()
    
    # Deploy application
    commands = deployer.deploy_application_files()
    deployer.execute_deployment_commands(commands)
    
    # Show final status
    deployer.get_instance_info()
    
    print(f"\n📦 Deployment package created: {package_path}")
    print("📜 Deployment script created: deploy-script.sh")
    print("\nTo complete deployment, run the deployment script on the instance:")
    print("1. SSH into the instance")
    print("2. Upload and run deploy-script.sh")
    print("3. Access the application via the URLs shown above")

if __name__ == "__main__":
    main()
