#!/bin/bash
echo "Updating GitHub secrets with fresh AWS credentials..."

# Update AWS Access Key ID
echo "$AWS_ACCESS_KEY_ID" | gh secret set AWS_ACCESS_KEY_ID --repo naveenraj44125-creator/github-actions-lightsail-demo

# Update AWS Secret Access Key  
echo "$AWS_SECRET_ACCESS_KEY" | gh secret set AWS_SECRET_ACCESS_KEY --repo naveenraj44125-creator/github-actions-lightsail-demo

# Update AWS Session Token
echo "$AWS_SESSION_TOKEN" | gh secret set AWS_SESSION_TOKEN --repo naveenraj44125-creator/github-actions-lightsail-demo

echo "All secrets updated successfully!"
