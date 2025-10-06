#!/bin/bash

# Demo Deployment Script for GitHub Actions + Lightsail
# This script demonstrates the complete deployment process

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

# Configuration
GITHUB_USERNAME="naveenraj44125-creator"
REPO_NAME="github-actions-lightsail-demo"
AWS_ACCOUNT="257429339749"
AWS_REGION="us-east-1"

echo -e "${PURPLE}🎬 GitHub Actions + Lightsail Deployment Demo${NC}"
echo -e "${PURPLE}=============================================${NC}"
echo ""
echo -e "👤 GitHub User: ${GREEN}${GITHUB_USERNAME}${NC}"
echo -e "📦 Repository: ${GREEN}${REPO_NAME}${NC}"
echo -e "☁️  AWS Account: ${GREEN}${AWS_ACCOUNT}${NC}"
echo -e "🌍 Region: ${GREEN}${AWS_REGION}${NC}"
echo ""

# Function to print demo steps
demo_step() {
    echo -e "\n${YELLOW}🎯 Demo Step $1: $2${NC}"
    echo "================================================"
}

# Function to simulate typing
type_command() {
    echo -e "${BLUE}$ $1${NC}"
    sleep 1
}

# Function to show file content
show_file() {
    echo -e "\n${GREEN}📄 $1:${NC}"
    echo "----------------------------------------"
    if [ -f "$1" ]; then
        head -20 "$1"
        if [ $(wc -l < "$1") -gt 20 ]; then
            echo "... (truncated, see full file for complete content)"
        fi
    else
        echo -e "${RED}File not found: $1${NC}"
    fi
    echo "----------------------------------------"
}

# Demo introduction
demo_step "1" "Project Overview"

echo -e "${BLUE}This demo shows how to:${NC}"
echo -e "• ✅ Set up GitHub repository with CI/CD pipeline"
echo -e "• ✅ Deploy Node.js application to AWS Lightsail"
echo -e "• ✅ Automate infrastructure with Terraform"
echo -e "• ✅ Monitor deployments and health checks"
echo ""

# Show project structure
demo_step "2" "Project Structure"

type_command "tree -I 'node_modules|.git'"
echo ""
echo "📁 Project Structure:"
echo "├── 🔄 .github/workflows/     # GitHub Actions CI/CD"
echo "├── 🚀 app/                   # Node.js Application"
echo "├── 🏗️  terraform/             # Infrastructure as Code"
echo "├── 🛠️  scripts/               # Automation Scripts"
echo "├── 📚 *.md                   # Documentation"
echo "└── 🔧 setup-github-repo.sh   # Repository Setup"

# Show key files
demo_step "3" "Key Configuration Files"

echo -e "${GREEN}🔄 GitHub Actions Workflow:${NC}"
show_file ".github/workflows/deploy-to-lightsail.yml"

echo -e "\n${GREEN}🚀 Node.js Application:${NC}"
show_file "app/server.js"

echo -e "\n${GREEN}🏗️ Terraform Infrastructure:${NC}"
show_file "terraform/main.tf"

# Show deployment process
demo_step "4" "Deployment Process Simulation"

echo -e "${BLUE}🔄 Simulating GitHub Actions Workflow:${NC}"
echo ""

echo -e "${YELLOW}Step 1: Code Push Trigger${NC}"
type_command "git push origin main"
echo -e "✅ Push detected, triggering workflow..."
sleep 1

echo -e "\n${YELLOW}Step 2: Environment Setup${NC}"
type_command "Setup Node.js 18.x"
echo -e "✅ Node.js environment ready"
sleep 1

echo -e "\n${YELLOW}Step 3: Install Dependencies${NC}"
type_command "npm install"
echo -e "✅ Dependencies installed"
sleep 1

echo -e "\n${YELLOW}Step 4: Run Tests${NC}"
type_command "npm test"
echo -e "✅ All tests passed"
sleep 1

echo -e "\n${YELLOW}Step 5: Deploy to Lightsail${NC}"
type_command "scp -r app/ ubuntu@lightsail-instance:/opt/app/"
echo -e "✅ Application files uploaded"
sleep 1

echo -e "\n${YELLOW}Step 6: Restart Services${NC}"
type_command "sudo systemctl restart lightsail-demo-app"
echo -e "✅ Application service restarted"
sleep 1

echo -e "\n${YELLOW}Step 7: Health Check${NC}"
type_command "curl http://instance-ip/health"
echo -e "✅ Health check passed"
sleep 1

# Show infrastructure
demo_step "5" "Infrastructure Components"

echo -e "${BLUE}🏗️ AWS Lightsail Resources Created:${NC}"
echo ""
echo -e "🖥️  ${GREEN}Lightsail Instance${NC}"
echo -e "   ├── OS: Ubuntu 22.04 LTS"
echo -e "   ├── Size: nano (512 MB RAM, 1 vCPU)"
echo -e "   ├── Cost: ~$3.50/month"
echo -e "   └── Auto-configured with Node.js & Nginx"
echo ""
echo -e "🌐 ${GREEN}Static IP Address${NC}"
echo -e "   ├── Fixed public IP for your app"
echo -e "   ├── DNS-friendly"
echo -e "   └── Included with instance"
echo ""
echo -e "🔒 ${GREEN}Security Group${NC}"
echo -e "   ├── SSH (22) - Your IP only"
echo -e "   ├── HTTP (80) - Public access"
echo -e "   └── HTTPS (443) - Public access"
echo ""
echo -e "🔑 ${GREEN}SSH Key Pair${NC}"
echo -e "   ├── Auto-generated"
echo -e "   ├── Stored in GitHub Secrets"
echo -e "   └── Used for deployment access"

# Show monitoring
demo_step "6" "Monitoring & Health Checks"

echo -e "${BLUE}📊 Application Endpoints:${NC}"
echo ""
echo -e "🏠 ${GREEN}http://your-instance-ip/${NC}"
echo -e "   └── Welcome page with app info"
echo ""
echo -e "❤️  ${GREEN}http://your-instance-ip/health${NC}"
echo -e "   └── Health check (used by load balancer)"
echo ""
echo -e "ℹ️  ${GREEN}http://your-instance-ip/api/info${NC}"
echo -e "   └── Application information"
echo ""
echo -e "🚀 ${GREEN}http://your-instance-ip/api/deploy-info${NC}"
echo -e "   └── Deployment details and version"

# Show GitHub Actions dashboard
demo_step "7" "GitHub Actions Dashboard"

echo -e "${BLUE}📈 Monitor your deployments at:${NC}"
echo -e "${GREEN}https://github.com/${GITHUB_USERNAME}/${REPO_NAME}/actions${NC}"
echo ""
echo -e "You can see:"
echo -e "• ✅ Workflow run history"
echo -e "• ✅ Build and test results"
echo -e "• ✅ Deployment status"
echo -e "• ✅ Execution time and logs"
echo -e "• ✅ Success/failure notifications"

# Show cost breakdown
demo_step "8" "Cost Breakdown"

echo -e "${BLUE}💰 Monthly AWS Costs:${NC}"
echo ""
echo -e "🖥️  Lightsail Instance (nano): ${GREEN}\$3.50/month${NC}"
echo -e "🌐 Static IP Address: ${GREEN}\$0.00${NC} (included)"
echo -e "📊 Data Transfer (1TB): ${GREEN}\$0.00${NC} (included)"
echo -e "🔒 Security Groups: ${GREEN}\$0.00${NC} (included)"
echo ""
echo -e "📋 ${GREEN}Total: ~\$3.50/month${NC}"
echo ""
echo -e "${YELLOW}💡 Tips to optimize costs:${NC}"
echo -e "• Use nano instance for development/testing"
echo -e "• Set up auto-shutdown for non-production"
echo -e "• Monitor data transfer usage"
echo -e "• Clean up unused snapshots"

# Show next steps
demo_step "9" "Ready to Deploy?"

echo -e "${BLUE}🚀 To set up this demo in your account:${NC}"
echo ""
echo -e "1. ${GREEN}Run the setup script:${NC}"
echo -e "   ${BLUE}./setup-github-repo.sh${NC}"
echo ""
echo -e "2. ${GREEN}Follow the GitHub setup guide:${NC}"
echo -e "   ${BLUE}cat GITHUB_SETUP.md${NC}"
echo ""
echo -e "3. ${GREEN}Configure AWS credentials:${NC}"
echo -e "   ${BLUE}aws configure${NC}"
echo ""
echo -e "4. ${GREEN}Deploy infrastructure:${NC}"
echo -e "   ${BLUE}./scripts/setup-infrastructure.sh${NC}"
echo ""
echo -e "5. ${GREEN}Set GitHub Secrets and push code${NC}"
echo ""

# Show security features
demo_step "10" "Security Features"

echo -e "${BLUE}🔒 Security Best Practices Included:${NC}"
echo ""
echo -e "🔑 ${GREEN}SSH Key Authentication${NC}"
echo -e "   └── No password-based access"
echo ""
echo -e "🛡️  ${GREEN}Security Groups${NC}"
echo -e "   └── Restricted port access"
echo ""
echo -e "🔐 ${GREEN}Application Security${NC}"
echo -e "   ├── Helmet.js security headers"
echo -e "   ├── Input validation"
echo -e "   └── Error handling"
echo ""
echo -e "👤 ${GREEN}Process Isolation${NC}"
echo -e "   └── Non-root application execution"
echo ""
echo -e "📝 ${GREEN}Audit Logging${NC}"
echo -e "   └── Request logging with Morgan"

# Demo conclusion
echo -e "\n${PURPLE}🎉 Demo Complete!${NC}"
echo -e "${PURPLE}=================${NC}"
echo ""
echo -e "${GREEN}✅ You now have a complete understanding of:${NC}"
echo -e "• GitHub Actions CI/CD pipeline setup"
echo -e "• AWS Lightsail infrastructure deployment"
echo -e "• Automated testing and deployment"
echo -e "• Security best practices"
echo -e "• Cost optimization strategies"
echo -e "• Monitoring and health checks"
echo ""
echo -e "${BLUE}📚 Documentation:${NC}"
echo -e "• ${GREEN}README.md${NC} - Project overview"
echo -e "• ${GREEN}GITHUB_SETUP.md${NC} - Step-by-step setup"
echo -e "• ${GREEN}SETUP_GUIDE.md${NC} - Technical details"
echo ""
echo -e "${YELLOW}🚀 Ready to deploy? Run: ${GREEN}./setup-github-repo.sh${NC}"
echo ""
echo -e "${BLUE}Repository: ${GREEN}https://github.com/${GITHUB_USERNAME}/${REPO_NAME}${NC}"
echo -e "${BLUE}AWS Account: ${GREEN}${AWS_ACCOUNT}${NC}"
echo -e "${BLUE}Region: ${GREEN}${AWS_REGION}${NC}"
