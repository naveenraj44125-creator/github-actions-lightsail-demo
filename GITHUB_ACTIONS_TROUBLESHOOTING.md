# GitHub Actions Troubleshooting Guide

## 🚨 Issues Fixed (Latest Update)

Your GitHub Actions workflow has been updated with the following critical fixes:

### 1. **Missing AWS Session Token** ✅ FIXED
- **Issue**: Workflow was missing `aws-session-token` parameter for AWS credentials
- **Fix**: Added `aws-session-token: ${{ secrets.AWS_SESSION_TOKEN }}` to all AWS credential configurations

### 2. **Unreliable SSH-based Deployment** ✅ FIXED
- **Issue**: SSH key management was complex and error-prone
- **Fix**: Replaced SSH deployment with Python script using AWS Lightsail run commands
- **Added**: `deploy-with-run-command.py` script for reliable deployment

### 3. **Workflow Structure Improvements** ✅ FIXED
- **Issue**: Complex SSH setup and deployment logic
- **Fix**: Simplified deployment using AWS native access methods
- **Removed**: SSH key output and complex SSH setup steps
- **Added**: Python environment setup and boto3 installation

### 4. **Deployment Method Modernization** ✅ FIXED
- **Issue**: Manual SSH commands were brittle and hard to debug
- **Fix**: Using AWS Lightsail `get-instance-access-details` API for secure access
- **Benefit**: More reliable, better error handling, cleaner logs

## 🔧 Required Setup

### Step 1: Set Up GitHub Secrets

Run the setup script to configure required secrets:

```bash
./setup-github-secrets.sh
```

**Required Secrets:**
- `AWS_ACCESS_KEY_ID` - Your AWS Access Key ID
- `AWS_SECRET_ACCESS_KEY` - Your AWS Secret Access Key  
- `AWS_SESSION_TOKEN` - AWS Session Token (REQUIRED for temporary credentials)

**Note**: The `LIGHTSAIL_SSH_KEY` secret is no longer needed as the workflow now uses AWS run commands instead of SSH keys.

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

3. **Verify Deployment:**
   - The workflow now uses AWS run commands instead of SSH keys
   - No manual SSH key setup is required
   - Monitor the deployment through GitHub Actions logs

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

### Issue: "AWS run command failed"
**Solution:**
- Verify AWS credentials have proper Lightsail permissions
- Check that the instance is running and accessible
- Ensure the instance has the latest AWS SSM agent (handled by user_data script)
- Review deployment logs in GitHub Actions for specific error messages

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
- Sets up Python environment and boto3
- Creates deployment package
- Uses AWS run commands to deploy application
- Installs dependencies on server via run commands
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
# SSH into your instance (get SSH details from Lightsail console)
ssh -i ~/.ssh/lightsail_key ubuntu@<INSTANCE_IP>

# Or use AWS CLI to run commands directly
aws lightsail get-instance-access-details --instance-name my-app-instance

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

**Last Updated**: October 8, 2025
**Workflow Version**: 3.0 (AWS Run Commands & Session Token Fix)
