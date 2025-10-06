# GitHub Actions + AWS Lightsail Setup Guide

This guide will walk you through setting up automated deployment from GitHub to AWS Lightsail using GitHub Actions.

## Prerequisites

Before you begin, ensure you have:

- AWS Account (Account ID: 257429339749)
- GitHub repository
- Local development environment with:
  - [Terraform](https://www.terraform.io/downloads.html) installed
  - [AWS CLI](https://aws.amazon.com/cli/) installed
  - Git installed

## Quick Start

### 1. Configure AWS Credentials

Set up your AWS credentials using Ada or AWS CLI:

```bash
# Option 1: Using Ada (recommended for Amazon employees)
ada credentials update --account=257429339749 --role=Admin

# Option 2: Using AWS CLI
aws configure
```

Verify your credentials:
```bash
aws sts get-caller-identity
```

### 2. Deploy Infrastructure

Run the automated setup script:

```bash
./scripts/setup-infrastructure.sh
```

Or deploy manually:

```bash
cd terraform
terraform init
terraform plan -var="instance_name=my-app-instance"
terraform apply
```

### 3. Configure GitHub Secrets

Add these secrets to your GitHub repository (Settings → Secrets and variables → Actions):

| Secret Name | Description | How to Get |
|-------------|-------------|------------|
| `AWS_ACCESS_KEY_ID` | AWS Access Key | From AWS credentials |
| `AWS_SECRET_ACCESS_KEY` | AWS Secret Key | From AWS credentials |
| `LIGHTSAIL_SSH_KEY` | SSH Private Key | From Terraform output |

To get the SSH key:
```bash
cd terraform
terraform output -raw ssh_private_key
```

### 4. Update Workflow Configuration

Edit `.github/workflows/deploy-to-lightsail.yml` and update:

```yaml
env:
  AWS_REGION: us-east-1
  LIGHTSAIL_INSTANCE_NAME: my-app-instance  # Use your instance name
```

### 5. Test Deployment

1. Commit and push your code to the main branch
2. Go to GitHub → Actions tab
3. Watch the deployment workflow run
4. Access your application at the provided URL

## Detailed Setup Steps

### Infrastructure Components

The Terraform configuration creates:

- **Lightsail Instance**: Ubuntu 20.04 with Node.js pre-installed
- **Static IP**: Fixed IP address for your application
- **Security Groups**: Configured for HTTP, HTTPS, and SSH access
- **SSH Key Pair**: For secure access to the instance
- **Optional**: Container Service and Database

### Application Structure

```
├── .github/workflows/          # GitHub Actions workflows
│   └── deploy-to-lightsail.yml # Main deployment workflow
├── app/                        # Node.js application
│   ├── server.js              # Express.js server
│   ├── package.json           # Dependencies
│   ├── Dockerfile             # Container configuration
│   └── server.test.js         # Tests
├── terraform/                  # Infrastructure as Code
│   ├── main.tf                # Main Terraform configuration
│   ├── variables.tf           # Input variables
│   ├── outputs.tf             # Output values
│   └── user_data.sh           # Instance initialization script
└── scripts/                   # Utility scripts
    └── setup-infrastructure.sh # Automated setup
```

### Deployment Workflow

The GitHub Actions workflow:

1. **Checkout**: Gets the latest code
2. **Setup**: Configures Node.js and AWS credentials
3. **Test**: Runs application tests
4. **Build**: Builds the application
5. **Deploy**: 
   - Gets instance IP from AWS
   - Copies files via SCP
   - Restarts services via SSH
6. **Verify**: Performs health check

### Customization Options

#### Instance Configuration

Modify `terraform/variables.tf` to customize:

```hcl
variable "bundle_id" {
  default = "micro_2_0"  # Change instance size
}

variable "blueprint_id" {
  default = "ubuntu_22_04"  # Change OS
}
```

#### Application Settings

Update environment variables in the workflow:

```yaml
env:
  NODE_ENV: production
  PORT: 3000
  # Add your custom variables
```

#### Container Deployment

To use Lightsail Container Service instead:

1. Set `create_container_service = true` in Terraform
2. Enable the container deployment job in the workflow:
   ```yaml
   deploy-container:
     if: true  # Change from false to true
   ```

## Monitoring and Troubleshooting

### Health Checks

The application provides several endpoints:

- `/` - Welcome message
- `/health` - Health status
- `/api/info` - Application information
- `/api/deploy-info` - Deployment details

### Logs

View application logs:

```bash
# SSH into the instance
ssh -i your-key.pem ubuntu@YOUR_INSTANCE_IP

# View application logs
sudo journalctl -u lightsail-demo-app -f

# View Nginx logs
sudo tail -f /var/log/nginx/access.log
sudo tail -f /var/log/nginx/error.log
```

### Common Issues

#### Deployment Fails

1. Check GitHub Actions logs
2. Verify AWS credentials and permissions
3. Ensure instance is running and accessible
4. Check SSH key configuration

#### Application Not Accessible

1. Verify security group settings
2. Check if services are running:
   ```bash
   sudo systemctl status lightsail-demo-app
   sudo systemctl status nginx
   ```
3. Test local connectivity:
   ```bash
   curl http://localhost:3000/health
   ```

#### SSH Connection Issues

1. Verify SSH key permissions: `chmod 600 your-key.pem`
2. Check security group allows SSH (port 22)
3. Ensure correct username (ubuntu for Ubuntu instances)

## Security Best Practices

### GitHub Secrets

- Never commit AWS credentials to your repository
- Use GitHub Secrets for sensitive information
- Rotate credentials regularly

### Instance Security

- Keep the OS and packages updated
- Use SSH keys instead of passwords
- Limit security group access to necessary ports
- Enable CloudWatch monitoring

### Application Security

- Use HTTPS in production (configure SSL certificate)
- Implement proper error handling
- Validate all inputs
- Use environment variables for configuration

## Scaling and Production Considerations

### High Availability

For production workloads, consider:

- Multiple instances across availability zones
- Load balancer (Application Load Balancer)
- Database replication
- Automated backups

### Performance Optimization

- Use Lightsail CDN for static assets
- Implement caching strategies
- Monitor application metrics
- Optimize database queries

### Cost Optimization

- Choose appropriate instance sizes
- Use reserved instances for predictable workloads
- Monitor usage with AWS Cost Explorer
- Clean up unused resources

## Advanced Features

### Blue-Green Deployment

Implement zero-downtime deployments:

1. Create two identical environments
2. Deploy to inactive environment
3. Switch traffic after validation
4. Keep previous version for rollback

### Database Integration

Add a managed database:

```hcl
# In terraform/main.tf
variable "create_database" {
  default = true
}
```

### Container Orchestration

Use Lightsail Container Service for:

- Automatic scaling
- Load balancing
- Health checks
- Rolling deployments

## Support and Resources

### Documentation

- [AWS Lightsail Documentation](https://docs.aws.amazon.com/lightsail/)
- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [Terraform AWS Provider](https://registry.terraform.io/providers/hashicorp/aws/latest/docs)

### Troubleshooting

If you encounter issues:

1. Check the GitHub Actions logs
2. Review AWS CloudTrail for API calls
3. Examine instance logs via SSH
4. Verify network connectivity and security groups

### Getting Help

- AWS Support (if you have a support plan)
- GitHub Community Forums
- Stack Overflow with relevant tags

## Next Steps

After successful deployment:

1. Set up monitoring and alerting
2. Implement automated testing
3. Configure SSL certificates
4. Set up backup strategies
5. Plan for disaster recovery

## Cleanup

To avoid ongoing charges, destroy resources when done:

```bash
cd terraform
terraform destroy
```

This will remove all created AWS resources.
