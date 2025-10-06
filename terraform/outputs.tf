output "instance_name" {
  description = "Name of the Lightsail instance"
  value       = aws_lightsail_instance.app_server.name
}

output "instance_arn" {
  description = "ARN of the Lightsail instance"
  value       = aws_lightsail_instance.app_server.arn
}

output "public_ip_address" {
  description = "Public IP address of the instance"
  value       = aws_lightsail_static_ip.app_static_ip.ip_address
}

output "private_ip_address" {
  description = "Private IP address of the instance"
  value       = aws_lightsail_instance.app_server.private_ip_address
}

output "instance_blueprint_id" {
  description = "Blueprint ID used for the instance"
  value       = aws_lightsail_instance.app_server.blueprint_id
}

output "instance_bundle_id" {
  description = "Bundle ID used for the instance"
  value       = aws_lightsail_instance.app_server.bundle_id
}

output "ssh_key_name" {
  description = "Name of the SSH key pair"
  value       = aws_lightsail_key_pair.app_key.name
}

output "ssh_private_key" {
  description = "Private SSH key for connecting to the instance"
  value       = aws_lightsail_key_pair.app_key.private_key
  sensitive   = true
}

output "ssh_public_key" {
  description = "Public SSH key"
  value       = aws_lightsail_key_pair.app_key.public_key
}

output "static_ip_name" {
  description = "Name of the static IP"
  value       = aws_lightsail_static_ip.app_static_ip.name
}

output "application_url" {
  description = "URL to access the application"
  value       = "http://${aws_lightsail_static_ip.app_static_ip.ip_address}:3000"
}

output "health_check_url" {
  description = "URL for health check endpoint"
  value       = "http://${aws_lightsail_static_ip.app_static_ip.ip_address}:3000/health"
}

output "ssh_connection_command" {
  description = "SSH command to connect to the instance"
  value       = "ssh -i ${aws_lightsail_key_pair.app_key.name}.pem ubuntu@${aws_lightsail_static_ip.app_static_ip.ip_address}"
}

output "container_service_name" {
  description = "Name of the Lightsail Container Service (if created)"
  value       = var.create_container_service ? aws_lightsail_container_service.app_container_service[0].name : null
}

output "container_service_url" {
  description = "URL of the Lightsail Container Service (if created)"
  value       = var.create_container_service ? "https://${aws_lightsail_container_service.app_container_service[0].url}" : null
}

output "database_endpoint" {
  description = "Database endpoint (if created)"
  value       = var.create_database ? aws_lightsail_database.app_database[0].master_endpoint_address : null
  sensitive   = true
}

output "database_port" {
  description = "Database port (if created)"
  value       = var.create_database ? aws_lightsail_database.app_database[0].master_endpoint_port : null
}

output "availability_zone" {
  description = "Availability zone where resources are deployed"
  value       = data.aws_availability_zones.available.names[0]
}

output "region" {
  description = "AWS region where resources are deployed"
  value       = var.aws_region
}

output "environment" {
  description = "Environment name"
  value       = var.environment
}

output "deployment_instructions" {
  description = "Instructions for setting up GitHub Actions deployment"
  value = <<-EOT
    To set up GitHub Actions deployment:
    
    1. Add these secrets to your GitHub repository:
       - AWS_ACCESS_KEY_ID: Your AWS access key
       - AWS_SECRET_ACCESS_KEY: Your AWS secret key
       - LIGHTSAIL_SSH_KEY: The private SSH key (see ssh_private_key output)
    
    2. Update the GitHub Actions workflow with:
       - Instance name: ${aws_lightsail_instance.app_server.name}
       - Instance IP: ${aws_lightsail_static_ip.app_static_ip.ip_address}
    
    3. SSH into the instance to set up the application directory:
       ssh -i ${aws_lightsail_key_pair.app_key.name}.pem ubuntu@${aws_lightsail_static_ip.app_static_ip.ip_address}
    
    4. Application will be available at:
       http://${aws_lightsail_static_ip.app_static_ip.ip_address}:3000
    
    5. Health check endpoint:
       http://${aws_lightsail_static_ip.app_static_ip.ip_address}:3000/health
  EOT
}
