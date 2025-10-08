# GitHub Actions Deployment for Lightsail QBR App

This document explains how to deploy the Lightsail QBR (Quarterly Business Review) application using GitHub Actions to AWS Lightsail.

## 🚀 Overview

The GitHub Actions workflow automatically:
1. **Checks for existing instance** named `lightsail-qbr`
2. **Creates new instance** if it doesn't exist (LAMP stack on Bitnami)
3. **Deploys the QBR application** files to the instance
4. **Configures the database** and sets up the application
5. **Provides access URLs** and login credentials

## 📋 Prerequisites

### 1. AWS Credentials Setup

You need to configure the following GitHub Secrets in your repository:

```
AWS_ACCESS_KEY_ID       # Your AWS Access Key ID
AWS_SECRET_ACCESS_KEY   # Your AWS Secret Access Key  
AWS_SESSION_TOKEN       # Your AWS Session Token (for SSO users)
```

#### For AWS SSO Users:
```bash
# Get temporary credentials
aws sso login --profile your-profile
aws configure export-credentials --profile your-profile --format env

# Copy the output values to GitHub Secrets
```

#### For IAM Users:
```bash
# Use your permanent IAM credentials
AWS_ACCESS_KEY_ID=AKIA...
AWS_SECRET_ACCESS_KEY=...
# AWS_SESSION_TOKEN not needed for IAM users
```

### 2. Required AWS Permissions

Your AWS credentials need the following Lightsail permissions:

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Allow",
            "Action": [
                "lightsail:GetInstance",
                "lightsail:GetInstances",
                "lightsail:CreateInstances",
                "lightsail:PutInstancePublicPorts",
                "lightsail:GetInstanceAccessDetails"
            ],
            "Resource": "*"
        }
    ]
}
```

## 🔧 Setup Instructions

### Step 1: Fork/Clone Repository

```bash
git clone <your-repository-url>
cd lightsail-qbr-app
```

### Step 2: Configure GitHub Secrets

1. Go to your GitHub repository
2. Navigate to **Settings** → **Secrets and variables** → **Actions**
3. Add the following secrets:
   - `AWS_ACCESS_KEY_ID`
   - `AWS_SECRET_ACCESS_KEY`
   - `AWS_SESSION_TOKEN` (if using AWS SSO)

### Step 3: Trigger Deployment

The workflow can be triggered in three ways:

#### Automatic Triggers:
- **Push to main/master branch**
- **Pull request to main/master branch**

#### Manual Trigger:
1. Go to **Actions** tab in your GitHub repository
2. Select **Deploy QBR App to Lightsail** workflow
3. Click **Run workflow**
4. Select branch and click **Run workflow**

## 📊 Workflow Steps

### 1. Instance Check & Creation
```yaml
- Checks if 'lightsail-qbr' instance exists
- Creates new LAMP stack instance if needed
- Waits for instance to be running and ready
```

### 2. Application Deployment
```yaml
- Runs Python deployment script
- Creates application directories
- Deploys PHP files, CSS, JavaScript
- Sets up MySQL database and user
- Configures proper permissions
```

### 3. Final Configuration
```yaml
- Configures firewall ports (22, 80, 443)
- Provides deployment summary
- Shows application URLs and credentials
```

## 🎯 Deployment Outputs

After successful deployment, you'll see:

```
=== Deployment Summary ===
Instance Name: lightsail-qbr
Instance State: running
Public IP: 18.XXX.XXX.XXX
Blueprint: lamp_8_bitnami
Bundle: nano_3_0

🚀 QBR Application URL: http://18.XXX.XXX.XXX/qbr-app/
📊 Admin Panel: http://18.XXX.XXX.XXX/qbr-app/admin/

📝 Default Login Credentials:
   Admin: admin / admin123
   Employee: employee / employee123

✅ Deployment completed successfully!
```

## 🔐 Security Configuration

### Default Credentials
The application comes with default test credentials:
- **Admin User**: `admin` / `admin123`
- **Employee User**: `employee` / `employee123`

⚠️ **Important**: Change these credentials immediately after first login!

### Database Configuration
- **Database**: `lightsail_qbr`
- **User**: `qbr_user`
- **Password**: `qbr_password_2025`

### Firewall Ports
The workflow automatically opens:
- **Port 22**: SSH access
- **Port 80**: HTTP web access
- **Port 443**: HTTPS (for future SSL setup)

## 🛠 Manual Deployment (Alternative)

If you prefer to deploy manually:

```bash
# Install dependencies
pip install boto3

# Run deployment script
python3 deploy-qbr-to-lightsail.py
```

This creates:
- `qbr-deployment.tar.gz` - Application package
- `deploy-script.sh` - Deployment script for the instance

## 📁 Application Structure

The deployed application includes:

```
/opt/bitnami/apache/htdocs/qbr-app/
├── admin/                    # Admin panel
│   ├── add-project.php      # Project creation
│   ├── manage-projects.php  # Project management
│   └── users.php            # User management
├── assets/                   # Static assets
│   ├── css/style.css        # Custom styling
│   └── js/main.js           # JavaScript functionality
├── config/                   # Configuration
│   └── database.php         # Database settings
├── includes/                 # Shared includes
│   └── auth.php             # Authentication functions
├── index.php                 # Main dashboard
├── login.php                 # User login
├── register.php              # User registration
├── project-details.php       # Project details view
├── logout.php                # Logout handler
└── setup-database.sql        # Database schema
```

## 🔍 Troubleshooting

### Common Issues

#### 1. AWS Credentials Error
```
Error: The security token included in the request is invalid
```
**Solution**: Refresh your AWS SSO session or check IAM credentials

#### 2. Instance Creation Timeout
```
Instance did not become ready in time
```
**Solution**: Check AWS Lightsail console for instance status

#### 3. Permission Denied
```
User: arn:aws:sts::xxx:assumed-role/xxx is not authorized to perform: lightsail:CreateInstances
```
**Solution**: Add required Lightsail permissions to your AWS role/user

#### 4. Database Connection Failed
```
SQLSTATE[HY000] [1045] Access denied for user 'qbr_user'@'localhost'
```
**Solution**: Check if database setup completed during instance creation

### Debug Steps

1. **Check GitHub Actions logs** for detailed error messages
2. **SSH into instance** to verify file deployment:
   ```bash
   ssh bitnami@<instance-ip>
   ls -la /opt/bitnami/apache/htdocs/qbr-app/
   ```
3. **Check Apache logs**:
   ```bash
   sudo tail -f /opt/bitnami/apache/logs/error_log
   ```
4. **Verify database**:
   ```bash
   mysql -u qbr_user -pqbr_password_2025 lightsail_qbr -e "SHOW TABLES;"
   ```

## 🔄 Updating the Application

To update the deployed application:

1. **Make changes** to your code
2. **Commit and push** to main/master branch
3. **GitHub Actions** will automatically redeploy
4. **Existing instance** will be updated (not recreated)

## 💰 Cost Considerations

### Lightsail Instance Costs
- **nano_3_0 bundle**: ~$3.50/month
- **Includes**: 512 MB RAM, 1 vCPU, 20 GB SSD, 1 TB transfer

### Cost Optimization
- **Stop instance** when not needed (billing continues)
- **Delete instance** to stop all charges
- **Upgrade bundle** if more resources needed

## 📞 Support

### Resources
- **AWS Lightsail Documentation**: https://docs.aws.amazon.com/lightsail/
- **GitHub Actions Documentation**: https://docs.github.com/en/actions
- **Application README**: [README.md](README.md)
- **Deployment Guide**: [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md)

### Getting Help
1. Check the **GitHub Actions logs** for error details
2. Review the **troubleshooting section** above
3. Verify **AWS credentials and permissions**
4. Check **Lightsail console** for instance status

---

**🎉 Congratulations!** Your QBR application should now be successfully deployed to AWS Lightsail using GitHub Actions automation.
