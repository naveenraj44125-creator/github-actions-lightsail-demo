# GitHub Setup Guide for naveenraj44125-creator

This guide shows you exactly how to set up this GitHub Actions + Lightsail project in your GitHub account and deploy to AWS Lightsail.

## 📋 Prerequisites

- GitHub account: `naveenraj44125-creator`
- AWS Account ID: `257429339749` with Admin role
- Local machine with Git, Node.js, and AWS CLI

## 🚀 Step-by-Step Setup

### Step 1: Create Repository on GitHub

1. **Go to GitHub**: https://github.com/naveenraj44125-creator
2. **Create new repository**:
   - Repository name: `github-actions-lightsail-demo`
   - Description: `GitHub Actions CI/CD pipeline for AWS Lightsail deployment`
   - Set to **Public** (for GitHub Actions free tier)
   - Initialize with README: **No** (we'll push our existing code)

### Step 2: Push Project to Your Repository

```bash
# Navigate to your project directory
cd /Users/naveenrp/Naveen/GIthub\ Actions\ in\ Lightsail/Cline

# Initialize git repository
git init

# Add all files
git add .

# Create initial commit
git commit -m "Initial commit: GitHub Actions + Lightsail CI/CD demo"

# Add your GitHub repository as remote
git remote add origin https://github.com/naveenraj44125-creator/github-actions-lightsail-demo.git

# Push to GitHub
git branch -M main
git push -u origin main
```

### Step 3: Configure AWS Credentials

#### Option A: Using AWS CLI (Recommended)
```bash
# Configure AWS CLI with your credentials
aws configure
# AWS Access Key ID: [Your Access Key]
# AWS Secret Access Key: [Your Secret Key]
# Default region name: us-east-1
# Default output format: json
```

#### Option B: Using Ada (Amazon Internal)
```bash
# If you're using Ada for AWS access
ada credentials update --account=257429339749 --role=Admin --provider=isengard
```

### Step 4: Set Up Infrastructure

```bash
# Make setup script executable
chmod +x scripts/setup-infrastructure.sh

# Run infrastructure setup
./scripts/setup-infrastructure.sh
```

This will:
- Create Lightsail instance
- Generate SSH key pair
- Set up security groups
- Configure static IP
- Output connection details

### Step 5: Configure GitHub Secrets

1. **Go to your repository**: https://github.com/naveenraj44125-creator/github-actions-lightsail-demo
2. **Navigate to**: Settings → Secrets and variables → Actions
3. **Add the following secrets**:

#### Required Secrets:
```
AWS_ACCESS_KEY_ID
├── Value: Your AWS Access Key ID
└── Used for: AWS API authentication

AWS_SECRET_ACCESS_KEY
├── Value: Your AWS Secret Access Key
└── Used for: AWS API authentication

LIGHTSAIL_SSH_KEY
├── Value: Contents of the private SSH key (from terraform output)
└── Used for: SSH access to Lightsail instance
```

#### How to get SSH key:
```bash
# After running terraform, get the SSH private key
cd terraform
terraform output -raw ssh_private_key > ../lightsail-key.pem
cat ../lightsail-key.pem
# Copy the entire content (including -----BEGIN and -----END lines)
```

### Step 6: Test Local Application

```bash
# Test the application locally first
./scripts/test-local.sh

# This will:
# - Install dependencies
# - Run tests
# - Start the server
# - Test endpoints
# - Stop the server
```

### Step 7: Trigger First Deployment

```bash
# Make a small change to trigger deployment
echo "# Deployment Test" >> README.md

# Commit and push
git add README.md
git commit -m "Test: Trigger first deployment"
git push origin main
```

### Step 8: Monitor Deployment

1. **Go to Actions tab**: https://github.com/naveenraj44125-creator/github-actions-lightsail-demo/actions
2. **Watch the workflow**: "Deploy to Lightsail"
3. **Check logs** for each step:
   - Test Application
   - Deploy to Lightsail Instance
   - Health Check

## 🔍 Verification Steps

### 1. Check GitHub Actions
- ✅ Workflow runs successfully
- ✅ All tests pass
- ✅ Deployment completes
- ✅ Health check passes

### 2. Check Lightsail Instance
```bash
# Get instance IP from terraform output
cd terraform
terraform output lightsail_static_ip

# Test the deployed application
curl http://[INSTANCE_IP]/health
curl http://[INSTANCE_IP]/api/info
```

### 3. Check Application Logs
```bash
# SSH into the instance
ssh -i lightsail-key.pem ubuntu@[INSTANCE_IP]

# Check application status
sudo systemctl status lightsail-demo-app

# View logs
sudo journalctl -u lightsail-demo-app -f
```

## 🛠️ Customization Options

### Change Application Port
```yaml
# In .github/workflows/deploy-to-lightsail.yml
env:
  APP_PORT: 3000  # Change this to your desired port
```

### Modify Instance Size
```hcl
# In terraform/variables.tf
variable "instance_bundle_id" {
  description = "Lightsail instance bundle ID"
  type        = string
  default     = "micro_2_0"  # Change to nano_2_0, small_2_0, etc.
}
```

### Add Environment Variables
```yaml
# In .github/workflows/deploy-to-lightsail.yml
- name: Deploy Application
  env:
    NODE_ENV: production
    DATABASE_URL: ${{ secrets.DATABASE_URL }}  # Add your secrets
    API_KEY: ${{ secrets.API_KEY }}
```

## 🚨 Troubleshooting

### Common Issues and Solutions

#### 1. GitHub Actions Fails
```bash
# Check secrets are set correctly
# Verify AWS credentials have proper permissions
# Check terraform output for correct values
```

#### 2. SSH Connection Fails
```bash
# Verify SSH key format in GitHub secrets
# Check security group allows SSH (port 22)
# Ensure instance is running
```

#### 3. Application Not Accessible
```bash
# Check security group allows HTTP (port 80)
# Verify nginx is running: sudo systemctl status nginx
# Check application logs: sudo journalctl -u lightsail-demo-app
```

#### 4. Deployment Hangs
```bash
# Check if previous deployment is still running
# Restart the application service: sudo systemctl restart lightsail-demo-app
# Check disk space: df -h
```

## 📊 Monitoring Your Deployment

### GitHub Actions Dashboard
- **URL**: https://github.com/naveenraj44125-creator/github-actions-lightsail-demo/actions
- **Monitor**: Deployment frequency, success rate, duration

### Application Endpoints
```bash
# Health check
curl http://[INSTANCE_IP]/health

# Application info
curl http://[INSTANCE_IP]/api/info

# Deployment info
curl http://[INSTANCE_IP]/api/deploy-info
```

### AWS Lightsail Console
- **URL**: https://lightsail.aws.amazon.com/
- **Monitor**: Instance metrics, networking, snapshots

## 🔄 Making Changes

### Code Changes
```bash
# Make your changes
vim app/server.js

# Test locally
./scripts/test-local.sh

# Commit and push
git add .
git commit -m "feat: Add new feature"
git push origin main

# GitHub Actions will automatically deploy
```

### Infrastructure Changes
```bash
# Modify terraform files
vim terraform/main.tf

# Plan changes
cd terraform
terraform plan

# Apply changes
terraform apply

# Update GitHub secrets if needed (new SSH keys, etc.)
```

## 🧹 Cleanup

### Remove GitHub Repository
1. Go to repository settings
2. Scroll to "Danger Zone"
3. Click "Delete this repository"

### Remove AWS Resources
```bash
cd terraform
terraform destroy
# Type 'yes' to confirm
```

## 🎯 Next Steps

1. **Add Database**: Integrate RDS or Lightsail database
2. **SSL Certificate**: Add HTTPS with Let's Encrypt
3. **Custom Domain**: Point your domain to the static IP
4. **Monitoring**: Add CloudWatch alarms and notifications
5. **Scaling**: Set up load balancer for multiple instances

## 📞 Support

- **GitHub Issues**: https://github.com/naveenraj44125-creator/github-actions-lightsail-demo/issues
- **AWS Documentation**: https://docs.aws.amazon.com/lightsail/
- **GitHub Actions Docs**: https://docs.github.com/en/actions

---

**Repository**: https://github.com/naveenraj44125-creator/github-actions-lightsail-demo
**AWS Account**: 257429339749
**Region**: us-east-1
