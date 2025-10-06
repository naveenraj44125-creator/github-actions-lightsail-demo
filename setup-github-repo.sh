#!/bin/bash

# GitHub Repository Setup Script for naveenraj44125-creator
# This script helps set up the GitHub Actions + Lightsail demo project

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
GITHUB_USERNAME="naveenraj44125-creator"
REPO_NAME="github-actions-lightsail-demo"
GITHUB_REPO="https://github.com/${GITHUB_USERNAME}/${REPO_NAME}.git"

echo -e "${BLUE}🚀 GitHub Actions + Lightsail Demo Setup${NC}"
echo -e "${BLUE}======================================${NC}"
echo ""
echo -e "Repository: ${GREEN}${GITHUB_REPO}${NC}"
echo -e "AWS Account: ${GREEN}257429339749${NC}"
echo -e "Region: ${GREEN}us-east-1${NC}"
echo ""

# Function to print step headers
print_step() {
    echo -e "\n${YELLOW}📋 Step $1: $2${NC}"
    echo "----------------------------------------"
}

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Check prerequisites
print_step "1" "Checking Prerequisites"

# Check Git
if ! command_exists git; then
    echo -e "${RED}❌ Git is not installed. Please install Git first.${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Git is installed${NC}"

# Check Node.js
if ! command_exists node; then
    echo -e "${RED}❌ Node.js is not installed. Please install Node.js first.${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Node.js is installed ($(node --version))${NC}"

# Check npm
if ! command_exists npm; then
    echo -e "${RED}❌ npm is not installed. Please install npm first.${NC}"
    exit 1
fi
echo -e "${GREEN}✅ npm is installed ($(npm --version))${NC}"

# Check AWS CLI
if ! command_exists aws; then
    echo -e "${YELLOW}⚠️  AWS CLI is not installed. You'll need it for infrastructure setup.${NC}"
    echo -e "${YELLOW}   Install it from: https://aws.amazon.com/cli/${NC}"
else
    echo -e "${GREEN}✅ AWS CLI is installed ($(aws --version))${NC}"
fi

# Check Terraform
if ! command_exists terraform; then
    echo -e "${YELLOW}⚠️  Terraform is not installed. You'll need it for infrastructure setup.${NC}"
    echo -e "${YELLOW}   Install it from: https://terraform.io/downloads${NC}"
else
    echo -e "${GREEN}✅ Terraform is installed ($(terraform --version | head -n1))${NC}"
fi

# Initialize Git repository
print_step "2" "Initializing Git Repository"

if [ -d ".git" ]; then
    echo -e "${YELLOW}⚠️  Git repository already exists${NC}"
    read -p "Do you want to reinitialize? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        rm -rf .git
        git init
        echo -e "${GREEN}✅ Git repository reinitialized${NC}"
    else
        echo -e "${BLUE}ℹ️  Using existing Git repository${NC}"
    fi
else
    git init
    echo -e "${GREEN}✅ Git repository initialized${NC}"
fi

# Create .gitignore if it doesn't exist
if [ ! -f ".gitignore" ]; then
    cat > .gitignore << EOF
# Dependencies
node_modules/
npm-debug.log*

# Environment variables
.env
.env.local
.env.production

# Terraform
*.tfstate
*.tfstate.*
.terraform/
.terraform.lock.hcl
terraform.tfvars

# SSH Keys
*.pem
*.key

# Logs
logs/
*.log

# OS generated files
.DS_Store
.DS_Store?
._*
.Spotlight-V100
.Trashes
ehthumbs.db
Thumbs.db

# IDE
.vscode/
.idea/
*.swp
*.swo

# Build outputs
dist/
build/
EOF
    echo -e "${GREEN}✅ Created .gitignore file${NC}"
fi

# Test the application locally
print_step "3" "Testing Application Locally"

echo -e "${BLUE}ℹ️  Installing dependencies...${NC}"
cd app
npm install
echo -e "${GREEN}✅ Dependencies installed${NC}"

echo -e "${BLUE}ℹ️  Running tests...${NC}"
npm test
echo -e "${GREEN}✅ Tests passed${NC}"

cd ..

# Add files to Git
print_step "4" "Adding Files to Git"

git add .
echo -e "${GREEN}✅ Files added to Git${NC}"

# Create initial commit
if git diff --cached --quiet; then
    echo -e "${YELLOW}⚠️  No changes to commit${NC}"
else
    git commit -m "Initial commit: GitHub Actions + Lightsail CI/CD demo

- Complete CI/CD pipeline with GitHub Actions
- Terraform infrastructure as code for Lightsail
- Node.js Express application with health checks
- Automated testing and deployment
- Security best practices and monitoring

Repository: ${GITHUB_REPO}
AWS Account: 257429339749
Region: us-east-1"
    echo -e "${GREEN}✅ Initial commit created${NC}"
fi

# Set up remote repository
print_step "5" "Setting Up Remote Repository"

# Check if remote already exists
if git remote get-url origin >/dev/null 2>&1; then
    current_remote=$(git remote get-url origin)
    if [ "$current_remote" != "$GITHUB_REPO" ]; then
        echo -e "${YELLOW}⚠️  Remote origin exists with different URL: $current_remote${NC}"
        read -p "Do you want to update it to $GITHUB_REPO? (y/N): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            git remote set-url origin "$GITHUB_REPO"
            echo -e "${GREEN}✅ Remote origin updated${NC}"
        fi
    else
        echo -e "${GREEN}✅ Remote origin already set correctly${NC}"
    fi
else
    git remote add origin "$GITHUB_REPO"
    echo -e "${GREEN}✅ Remote origin added${NC}"
fi

# Set main branch
git branch -M main
echo -e "${GREEN}✅ Main branch set${NC}"

# Instructions for GitHub repository creation
print_step "6" "GitHub Repository Setup Instructions"

echo -e "${BLUE}📝 Please follow these steps to create your GitHub repository:${NC}"
echo ""
echo -e "1. Go to: ${GREEN}https://github.com/${GITHUB_USERNAME}${NC}"
echo -e "2. Click '${GREEN}New${NC}' to create a new repository"
echo -e "3. Repository name: ${GREEN}${REPO_NAME}${NC}"
echo -e "4. Description: ${GREEN}GitHub Actions CI/CD pipeline for AWS Lightsail deployment${NC}"
echo -e "5. Set to ${GREEN}Public${NC} (for GitHub Actions free tier)"
echo -e "6. ${RED}DO NOT${NC} initialize with README (we have our own files)"
echo -e "7. Click '${GREEN}Create repository${NC}'"
echo ""

read -p "Press Enter when you've created the repository on GitHub..."

# Push to GitHub
print_step "7" "Pushing to GitHub"

echo -e "${BLUE}ℹ️  Pushing to GitHub repository...${NC}"
if git push -u origin main; then
    echo -e "${GREEN}✅ Successfully pushed to GitHub!${NC}"
else
    echo -e "${RED}❌ Failed to push to GitHub${NC}"
    echo -e "${YELLOW}💡 Make sure you've created the repository on GitHub and have proper access${NC}"
    exit 1
fi

# Next steps
print_step "8" "Next Steps"

echo -e "${BLUE}🎉 Repository setup complete!${NC}"
echo ""
echo -e "${YELLOW}📋 Next steps to deploy to Lightsail:${NC}"
echo ""
echo -e "1. ${GREEN}Configure AWS credentials:${NC}"
echo -e "   aws configure"
echo -e "   # OR for Amazon internal: ada credentials update --account=257429339749 --role=Admin"
echo ""
echo -e "2. ${GREEN}Set up infrastructure:${NC}"
echo -e "   chmod +x scripts/setup-infrastructure.sh"
echo -e "   ./scripts/setup-infrastructure.sh"
echo ""
echo -e "3. ${GREEN}Configure GitHub Secrets:${NC}"
echo -e "   - Go to: https://github.com/${GITHUB_USERNAME}/${REPO_NAME}/settings/secrets/actions"
echo -e "   - Add: AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, LIGHTSAIL_SSH_KEY"
echo ""
echo -e "4. ${GREEN}Test deployment:${NC}"
echo -e "   echo '# Test deployment' >> README.md"
echo -e "   git add README.md"
echo -e "   git commit -m 'Test: Trigger deployment'"
echo -e "   git push origin main"
echo ""
echo -e "5. ${GREEN}Monitor deployment:${NC}"
echo -e "   https://github.com/${GITHUB_USERNAME}/${REPO_NAME}/actions"
echo ""
echo -e "${BLUE}📚 For detailed instructions, see:${NC}"
echo -e "   - ${GREEN}GITHUB_SETUP.md${NC} - Complete setup guide"
echo -e "   - ${GREEN}SETUP_GUIDE.md${NC} - Technical documentation"
echo -e "   - ${GREEN}README.md${NC} - Project overview"
echo ""
echo -e "${GREEN}🚀 Repository URL: https://github.com/${GITHUB_USERNAME}/${REPO_NAME}${NC}"
echo -e "${GREEN}🔧 AWS Account: 257429339749${NC}"
echo -e "${GREEN}🌍 Region: us-east-1${NC}"
