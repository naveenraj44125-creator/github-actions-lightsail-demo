# GitHub Actions with AWS Lightsail Demo

This project demonstrates how to use GitHub Actions to automatically deploy applications to AWS Lightsail instances. It provides a complete CI/CD pipeline with infrastructure as code, automated testing, and deployment.

**Repository**: https://github.com/naveenraj44125-creator/github-actions-lightsail-demo

## 🚀 Quick Start

1. **Fork this repository to your GitHub account**: https://github.com/naveenraj44125-creator/github-actions-lightsail-demo
2. **Clone your forked repository**
3. **Run the setup script**: `./scripts/setup-infrastructure.sh`
4. **Configure GitHub Secrets** (see [SETUP_GUIDE.md](SETUP_GUIDE.md))
5. **Push to main branch** to trigger deployment

## 📋 Account Information
- AWS Account ID: 257429339749
- Role: Admin
- Region: us-east-1

## 🏗️ Project Structure

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
├── scripts/                   # Utility scripts
│   ├── setup-infrastructure.sh # Automated infrastructure setup
│   └── test-local.sh          # Local testing script
├── README.md                  # This file
└── SETUP_GUIDE.md            # Detailed setup instructions
```

## 🛠️ Features

### Infrastructure
- **Lightsail Instance**: Ubuntu 20.04 with Node.js pre-installed
- **Static IP**: Fixed IP address for your application
- **Security Groups**: Configured for HTTP, HTTPS, and SSH access
- **SSH Key Pair**: Automatically generated for secure access
- **Nginx Reverse Proxy**: Load balancing and SSL termination ready
- **Systemd Service**: Automatic application startup and monitoring

### CI/CD Pipeline
- **Automated Testing**: Jest unit tests run on every push
- **Build Process**: Application building and dependency management
- **Deployment**: Zero-downtime deployment via SSH
- **Health Checks**: Automatic verification of deployment success
- **Rollback**: Automatic backup of previous versions

### Application
- **Express.js API**: RESTful endpoints with proper error handling
- **Health Monitoring**: Built-in health check endpoints
- **Security**: Helmet.js security middleware
- **Logging**: Morgan request logging
- **Graceful Shutdown**: Proper signal handling

## 🚦 Deployment Methods

### 1. Direct SSH Deployment (Default)
- SSH into instance and deploy via SCP
- Restart services using systemd
- Automatic backup of previous versions
- Health check verification

### 2. Container Deployment (Optional)
- Build Docker images in GitHub Actions
- Deploy to Lightsail Container Service
- Automatic scaling and load balancing
- Rolling deployments

### 3. Infrastructure Provisioning
- Terraform-managed infrastructure
- Version-controlled infrastructure changes
- Automated resource creation and management

## 📚 Documentation

- **[SETUP_GUIDE.md](SETUP_GUIDE.md)** - Comprehensive setup instructions
- **[Terraform Documentation](terraform/)** - Infrastructure configuration details
- **[Application Documentation](app/)** - Node.js application details

## 🧪 Testing

### Local Testing
```bash
# Test the application locally
./scripts/test-local.sh

# Skip tests and just run the app
./scripts/test-local.sh --skip-tests
```

### Automated Testing
- Unit tests run automatically in GitHub Actions
- Integration tests verify deployment success
- Health checks ensure application availability

## 🔧 Configuration

### Environment Variables
```yaml
# GitHub Actions Workflow
AWS_REGION: us-east-1
LIGHTSAIL_INSTANCE_NAME: my-app-instance
NODE_ENV: production
```

### GitHub Secrets Required
- `AWS_ACCESS_KEY_ID` - Your AWS access key
- `AWS_SECRET_ACCESS_KEY` - Your AWS secret key  
- `LIGHTSAIL_SSH_KEY` - SSH private key for instance access

## 📊 Monitoring

### Application Endpoints
- `/` - Welcome message and status
- `/health` - Health check (used by load balancer)
- `/api/info` - Application information
- `/api/deploy-info` - Deployment details

### Logs
```bash
# Application logs
sudo journalctl -u lightsail-demo-app -f

# Nginx logs
sudo tail -f /var/log/nginx/access.log
```

## 🔒 Security Features

- **SSH Key Authentication**: No password-based access
- **Security Groups**: Restricted port access
- **Helmet.js**: Security headers and protection
- **Input Validation**: Request validation and sanitization
- **Error Handling**: Secure error responses
- **Process Isolation**: Non-root application execution

## 💰 Cost Optimization

- **Nano Instance**: $3.50/month for development
- **Static IP**: $0 (included with instance)
- **Data Transfer**: 1TB included
- **Automatic Shutdown**: Optional scheduled shutdowns

## 🚀 Production Considerations

### Scaling
- Multiple instances with load balancer
- Database replication
- CDN for static assets
- Auto-scaling groups

### Security
- SSL/TLS certificates
- WAF protection
- VPC networking
- Regular security updates

### Monitoring
- CloudWatch metrics
- Application performance monitoring
- Log aggregation
- Alerting and notifications

## 🛠️ Troubleshooting

### Common Issues
1. **Deployment fails**: Check GitHub Actions logs and AWS credentials
2. **Application not accessible**: Verify security groups and service status
3. **SSH connection issues**: Check key permissions and security groups

### Debug Commands
```bash
# Check service status
sudo systemctl status lightsail-demo-app

# View recent logs
sudo journalctl -u lightsail-demo-app --since "10 minutes ago"

# Test local connectivity
curl http://localhost:3000/health
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test locally with `./scripts/test-local.sh`
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🆘 Support

- **Documentation**: [SETUP_GUIDE.md](SETUP_GUIDE.md)
- **Issues**: GitHub Issues tab
- **AWS Support**: If you have an AWS support plan

## 🧹 Cleanup

To avoid ongoing charges:
```bash
cd terraform
terraform destroy
```

This will remove all created AWS resources.
