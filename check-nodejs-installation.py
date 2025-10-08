#!/usr/bin/env python3

"""
Check Node.js Installation on Lightsail Instance
===============================================
Use AWS Lightsail API to safely check Node.js installation status
without managing SSH keys manually.
"""

import boto3
import subprocess
import tempfile
import os

def create_ssh_files(ssh_details):
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

def run_command_on_instance(instance_name, command, timeout=60):
    """Execute command on Lightsail instance using AWS API"""
    try:
        print(f"🔧 [{instance_name}] {command}")
        
        lightsail = boto3.client('lightsail', region_name='us-east-1')
        ssh_response = lightsail.get_instance_access_details(instanceName=instance_name)
        ssh_details = ssh_response['accessDetails']
        
        key_path, cert_path = create_ssh_files(ssh_details)
        
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
                    for line in result.stdout.strip().split('\n'):
                        print(f"   {line}")
                return True, result.stdout.strip()
            else:
                print(f"   ❌ Failed (exit code: {result.returncode})")
                if result.stderr.strip():
                    for line in result.stderr.strip().split('\n'):
                        print(f"   ERROR: {line}")
                return False, result.stderr.strip()
            
        finally:
            try:
                os.unlink(key_path)
                os.unlink(cert_path)
            except:
                pass
            
    except Exception as e:
        print(f"   ❌ Error: {str(e)}")
        return False, str(e)

def check_nodejs_installation():
    """Check Node.js installation status on the Lightsail instance"""
    instance_name = 'my-app-instance'
    
    print("🔍 Checking Node.js Installation Status")
    print("=" * 50)
    
    # Check user_data script execution
    print("\n📋 Checking user_data script execution logs:")
    success, output = run_command_on_instance(instance_name, "sudo tail -50 /var/log/cloud-init-output.log")
    
    print("\n📋 Checking if user_data completion marker exists:")
    success, output = run_command_on_instance(instance_name, "ls -la /var/log/user-data-complete.marker")
    
    print("\n🔍 Checking Node.js installation:")
    commands = [
        "which node",
        "node --version",
        "which npm", 
        "npm --version",
        "ls -la /usr/bin/node*",
        "ls -la /usr/bin/npm*",
        "ls -la /snap/bin/node*",
        "ls -la /snap/bin/npm*",
        "echo $PATH"
    ]
    
    for cmd in commands:
        success, output = run_command_on_instance(instance_name, cmd)
    
    print("\n🔍 Checking snap packages:")
    success, output = run_command_on_instance(instance_name, "snap list | grep node")
    
    print("\n🔍 Checking apt packages:")
    success, output = run_command_on_instance(instance_name, "dpkg -l | grep node")
    
    print("\n🔍 Checking if NodeSource repository was added:")
    success, output = run_command_on_instance(instance_name, "ls -la /etc/apt/sources.list.d/ | grep node")
    
    print("\n🔍 Checking system logs for Node.js installation attempts:")
    success, output = run_command_on_instance(instance_name, "sudo grep -i node /var/log/cloud-init-output.log | tail -20")

if __name__ == "__main__":
    check_nodejs_installation()
