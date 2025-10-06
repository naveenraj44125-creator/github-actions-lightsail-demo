#!/bin/bash

# Setup script for GitHub Actions + Lightsail infrastructure
# This script helps you deploy the Lightsail infrastructure using Terraform

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
AWS_REGION="us-east-1"
INSTANCE_NAME="my-app-instance"
ENVIRONMENT="dev"

echo -e "${BLUE}GitHub Actions + Lightsail Infrastructure Setup${NC}"
echo "=============================================="

# Check if required tools are installed
check_requirements() {
    echo -e "${YELLOW}Checking requirements...${NC}"
    
    if ! command -v terraform &> /dev/null; then
        echo -e "${RED}Error: Terraform is not installed${NC}"
        echo "Please install Terraform: https://www.terraform.io/downloads.html"
        exit 1
    fi
    
    if ! command -v aws &> /dev/null; then
        echo -e "${RED}Error: AWS CLI is not installed${NC}"
        echo "Please install AWS CLI: https://aws.amazon.com/cli/"
        exit 1
    fi
    
    echo -e "${GREEN}✓ All requirements met${NC}"
}

# Configure AWS credentials
configure_aws() {
    echo -e "${YELLOW}Configuring AWS credentials...${NC}"
    
    if ! aws sts get-caller-identity &> /dev/null; then
        echo -e "${RED}AWS credentials not configured${NC}"
        echo "Please run: aws configure"
        echo "Or set up your credentials using environment variables"
        exit 1
    fi
    
    ACCOUNT_ID=$(aws sts get-caller-identity --query Account --output text)
    echo -e "${GREEN}✓ AWS configured for account: ${ACCOUNT_ID}${NC}"
}

# Initialize Terraform
init_terraform() {
    echo -e "${YELLOW}Initializing Terraform...${NC}"
    
    cd terraform
    terraform init
    
    echo -e "${GREEN}✓ Terraform initialized${NC}"
}

# Plan infrastructure
plan_infrastructure() {
    echo -e "${YELLOW}Planning infrastructure...${NC}"
    
    terraform plan \
        -var="aws_region=${AWS_REGION}" \
        -var="instance_name=${INSTANCE_NAME}" \
        -var="environment=${ENVIRONMENT}" \
        -out=tfplan
    
    echo -e "${GREEN}✓ Infrastructure plan created${NC}"
}

# Apply infrastructure
apply_infrastructure() {
    echo -e "${YELLOW}Applying infrastructure...${NC}"
    
    read -p "Do you want to apply the infrastructure changes? (y/N): " -n 1 -r
    echo
    
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        terraform apply tfplan
        echo -e "${GREEN}✓ Infrastructure deployed successfully${NC}"
    else
        echo -e "${YELLOW}Infrastructure deployment cancelled${NC}"
        exit 0
    fi
}

# Get outputs and setup instructions
show_outputs() {
    echo -e "${YELLOW}Getting infrastructure outputs...${NC}"
    
    INSTANCE_IP=$(terraform output -raw public_ip_address)
    SSH_KEY_NAME=$(terraform output -raw ssh_key_name)
    APPLICATION_URL=$(terraform output -raw application_url)
    HEALTH_URL=$(terraform output -raw health_check_url)
    
    echo -e "${GREEN}✓ Infrastructure deployed successfully!${NC}"
    echo
    echo "=== Deployment Information ==="
    echo "Instance Name: ${INSTANCE_NAME}"
    echo "Instance IP: ${INSTANCE_IP}"
    echo "Application URL: ${APPLICATION_URL}"
    echo "Health Check: ${HEALTH_URL}"
    echo
    
    # Save SSH key
    echo -e "${YELLOW}Saving SSH private key...${NC}"
    terraform output -raw ssh_private_key > "../${SSH_KEY_NAME}.pem"
    chmod 600 "../${SSH_KEY_NAME}.pem"
    echo -e "${GREEN}✓ SSH key saved as ${SSH_KEY_NAME}.pem${NC}"
    
    echo
    echo "=== Next Steps ==="
    echo "1. Add these secrets to your GitHub repository:"
    echo "   - AWS_ACCESS_KEY_ID: Your AWS access key"
    echo "   - AWS_SECRET_ACCESS_KEY: Your AWS secret key"
    echo "   - LIGHTSAIL_SSH_KEY: Contents of ${SSH_KEY_NAME}.pem"
    echo
    echo "2. Update the GitHub Actions workflow:"
    echo "   - Set LIGHTSAIL_INSTANCE_NAME to: ${INSTANCE_NAME}"
    echo
    echo "3. Test SSH connection:"
    echo "   ssh -i ${SSH_KEY_NAME}.pem ubuntu@${INSTANCE_IP}"
    echo
    echo "4. Push code to GitHub to trigger deployment"
    echo
    echo "5. Monitor deployment in GitHub Actions tab"
}

# Cleanup function
cleanup() {
    echo -e "${YELLOW}Cleaning up temporary files...${NC}"
    rm -f terraform/tfplan
}

# Main execution
main() {
    echo "Starting infrastructure setup..."
    echo
    
    check_requirements
    configure_aws
    init_terraform
    plan_infrastructure
    apply_infrastructure
    show_outputs
    cleanup
    
    echo -e "${GREEN}Setup completed successfully!${NC}"
    echo "Your Lightsail infrastructure is ready for GitHub Actions deployment."
}

# Handle script interruption
trap cleanup EXIT

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --region)
            AWS_REGION="$2"
            shift 2
            ;;
        --instance-name)
            INSTANCE_NAME="$2"
            shift 2
            ;;
        --environment)
            ENVIRONMENT="$2"
            shift 2
            ;;
        --help)
            echo "Usage: $0 [OPTIONS]"
            echo "Options:"
            echo "  --region REGION          AWS region (default: us-east-1)"
            echo "  --instance-name NAME     Instance name (default: my-app-instance)"
            echo "  --environment ENV        Environment (default: dev)"
            echo "  --help                   Show this help message"
            exit 0
            ;;
        *)
            echo "Unknown option: $1"
            echo "Use --help for usage information"
            exit 1
            ;;
    esac
done

# Run main function
main
