# GitHub Actions Troubleshooting Guide

## 🚨 Issues Fixed

Your GitHub Actions workflow had several critical issues that have been resolved:

### 1. **Test Failures** ✅ FIXED
- **Issue**: Test was expecting "Welcome to Lightsail Demo App!" but server returns "Welcome to Lightsail Demo App! 🚀 Deployed via GitHub Actions!"
- **Fix**: Updated `app/server.test.js` to match the actual server response

### 2. **Workflow Structure** ✅ FIXED
- **Issue**: Complex, monolithic workflow that was hard to debug
- **Fix**: Split into 3 separate jobs:
  - `test`: Runs tests and builds
  - `deploy-infrastructure`: Deploys Terraform infrastructure
  - `deploy-application`: Deploys the Node.js application

### 3. **Missing Dependencies** ✅ FIXED
- **Issue**: Workflow assumed infrastructure already existed
- **Fix**: Added proper Terraform deployment step with state management

### 4. **Improved Error Handling** ✅ FIXED
- **Issue**: Poor error handling and debugging capabilities
- **Fix**: Added comprehensive health checks, retries, and status reporting

## 🔧 Required Setup

### Step 1: Set Up GitHub Secrets

Run the setup script to configure required secrets:

```bash
./setup-github-secrets.sh
```

**Required Secrets:**
- `AWS_ACCESS_KEY_ID` - Your AWS Access Key ID
- `AWS_SECRET_ACCESS_KEY` - Your AWS Secret Access Key  
- `AWS_SESSION_TOKEN` - AWS Session Token (if using temporary credentials)
- `LIGHTSAIL_SSH_KEY` - SSH private key (set after first deployment)

### Step 2: AWS Credentials Setup

You need AWS credentials with the following permissions:
- Lightsail full access
- EC2 describe permissions
- IAM permissions for key pair management

**For AWS SSO Users:**
```bash
aws sso login --profile your-profile
aws configure export-credentials --profile your-profile --format env
```

### Step 3: First Deployment

1. **Commit and push your changes:**
   ```bash
   git add .
   git commit -m "Fix GitHub Actions workflow and tests"
   git push origin main
   ```

2. **Monitor the deployment:**
   - Go to your GitHub repository
   - Click on "Actions" tab
   - Watch the workflow progress

3. **Update SSH Key (after first deployment):**
   - Go to AWS Lightsail Console → Account → SSH Keys
   - Download the private key for `my-app-instance-key`
   - Update the `LIGHTSAIL_SSH_KEY` secret with the actual key content

## 🔍 Common Issues & Solutions

### Issue: "AWS credentials not found"
**Solution:**
- Verify AWS secrets are set correctly in GitHub
- Check AWS credentials have proper permissions
- For SSO users, ensure session token is included

### Issue: "Terraform state lock"
**Solution:**
```bash
# If terraform state gets locked, unlock it:
cd terraform
terraform force-unlock <LOCK_ID>
```

### Issue: "SSH connection failed"
**Solution:**
- Ensure LIGHTSAIL_SSH_KEY secret contains the correct private key
- Verify the key format (should start with `-----BEGIN OPENSSH PRIVATE KEY-----`)
- Check that the instance is running and accessible

### Issue: "Health check failed"
**Solution:**
- Check if the application is running: `sudo systemctl status lightsail-demo-app`
- Verify nginx is proxying correctly: `sudo systemctl status nginx`
- Check application logs: `sudo journalctl -u lightsail-demo-app -f`

### Issue: "Tests failing locally"
**Solution:**
```bash
cd app
npm install
npm test
```

## 📊 Workflow Overview

### Job 1: Test (`test`)
- Checks out code
- Sets up Node.js 18
- Installs dependencies with npm cache
- Runs tests
- Builds application

### Job 2: Deploy Infrastructure (`deploy-infrastructure`)
- Only runs on main branch pushes
- Sets up AWS credentials
- Initializes and applies Terraform
- Gets Lightsail instance IP
- Waits for SSH to be ready
- Outputs instance IP for next job

### Job 3: Deploy Application (`deploy-application`)
- Depends on both test and infrastructure jobs
- Sets up SSH key for deployment
- Creates deployment package
- Copies application to Lightsail instance
- Installs dependencies on server
- Configures systemd service
- Performs health checks
- Reports deployment status

## 🚀 Deployment Process

1. **Code Push** → Triggers workflow
2. **Tests Run** → Validates application
3. **Infrastructure** → Creates/updates Lightsail instance
4. **Application Deploy** → Deploys Node.js app
5. **Health Check** → Verifies deployment success

## 📝 Monitoring & Debugging

### GitHub Actions Logs
- View detailed logs in GitHub Actions tab
- Each step shows command output and errors
- Download logs for offline analysis

### Server Logs
```bash
# SSH into your instance
ssh -i ~/.ssh/lightsail_key ubuntu@<INSTANCE_IP>

# Check application status
sudo systemctl status lightsail-demo-app

# View application logs
sudo journalctl -u lightsail-demo-app -f

# Check nginx status
sudo systemctl status nginx

# View nginx logs
sudo tail -f /var/log/nginx/access.log
sudo tail -f /var/log/nginx/error.log
```

### Application Endpoints
- **Main App**: `http://<INSTANCE_IP>/`
- **Health Check**: `http://<INSTANCE_IP>/health`
- **API Info**: `http://<INSTANCE_IP>/api/info`
- **Deploy Info**: `http://<INSTANCE_IP>/api/deploy-info`

## 🔄 Manual Deployment (if needed)

If GitHub Actions fails, you can deploy manually:

```bash
# 1. Deploy infrastructure
cd terraform
terraform init
terraform plan
terraform apply

# 2. Get instance IP
INSTANCE_IP=$(terraform output -raw lightsail_instance_public_ip)

# 3. Deploy application
cd ../
tar -czf app.tar.gz app/
scp -i ~/.ssh/lightsail_key app.tar.gz ubuntu@$INSTANCE_IP:/tmp/

# 4. SSH and deploy
ssh -i ~/.ssh/lightsail_key ubuntu@$INSTANCE_IP
# ... follow deployment steps from workflow
```

## 📞 Support

If you continue to have issues:

1. **Check the workflow logs** in GitHub Actions
2. **Verify all secrets** are set correctly
3. **Test locally** with `npm test` in the app directory
4. **Check AWS permissions** for your credentials
5. **Review this troubleshooting guide** for common solutions

## ✅ Success Indicators

Your deployment is successful when you see:
- ✅ All GitHub Actions jobs complete successfully
- ✅ Health check endpoint returns 200 status
- ✅ Application accessible at instance IP
- ✅ No errors in application logs

---

**Last Updated**: October 6, 2025
**Workflow Version**: 2.0 (Improved & Fixed)
