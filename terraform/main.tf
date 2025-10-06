terraform {
  required_version = ">= 1.0"
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
  }
}

provider "aws" {
  region = var.aws_region
}

# Data source for available Lightsail availability zones
data "aws_availability_zones" "available" {
  state = "available"
}

# Lightsail instance
resource "aws_lightsail_instance" "app_server" {
  name              = var.instance_name
  availability_zone = data.aws_availability_zones.available.names[0]
  blueprint_id      = var.blueprint_id
  bundle_id         = var.bundle_id
  key_pair_name     = aws_lightsail_key_pair.app_key.name

  user_data = templatefile("${path.module}/user_data.sh", {
    app_name = var.app_name
  })

  tags = {
    Name        = var.instance_name
    Environment = var.environment
    Project     = "GitHub-Actions-Lightsail-Demo"
    ManagedBy   = "Terraform"
  }
}

# Key pair for SSH access
resource "aws_lightsail_key_pair" "app_key" {
  name = "${var.instance_name}-key"
}

# Static IP for the instance
resource "aws_lightsail_static_ip" "app_static_ip" {
  name = "${var.instance_name}-static-ip"
}

# Attach static IP to instance
resource "aws_lightsail_static_ip_attachment" "app_static_ip_attachment" {
  static_ip_name = aws_lightsail_static_ip.app_static_ip.name
  instance_name  = aws_lightsail_instance.app_server.name
}

# Open ports for HTTP, HTTPS, and SSH
resource "aws_lightsail_instance_public_ports" "app_ports" {
  instance_name = aws_lightsail_instance.app_server.name

  port_info {
    protocol  = "tcp"
    from_port = 22
    to_port   = 22
    cidrs     = ["0.0.0.0/0"]
  }

  port_info {
    protocol  = "tcp"
    from_port = 80
    to_port   = 80
    cidrs     = ["0.0.0.0/0"]
  }

  port_info {
    protocol  = "tcp"
    from_port = 443
    to_port   = 443
    cidrs     = ["0.0.0.0/0"]
  }

  port_info {
    protocol  = "tcp"
    from_port = 3000
    to_port   = 3000
    cidrs     = ["0.0.0.0/0"]
  }
}

# Optional: Lightsail Container Service for containerized deployments
resource "aws_lightsail_container_service" "app_container_service" {
  count = var.create_container_service ? 1 : 0
  
  name  = "${var.instance_name}-container-service"
  power = "nano"
  scale = 1

  tags = {
    Name        = "${var.instance_name}-container-service"
    Environment = var.environment
    Project     = "GitHub-Actions-Lightsail-Demo"
    ManagedBy   = "Terraform"
  }
}

# Optional: Database for the application
resource "aws_lightsail_database" "app_database" {
  count = var.create_database ? 1 : 0

  relational_database_name = "${var.instance_name}-db"
  availability_zone        = data.aws_availability_zones.available.names[0]
  master_database_name     = var.db_name
  master_username          = var.db_username
  master_user_password     = var.db_password
  blueprint_id             = "mysql_8_0"
  bundle_id                = "micro_1_0"

  backup_retention_enabled = true
  preferred_backup_window  = "03:00-04:00"
  preferred_maintenance_window = "sun:04:00-sun:05:00"

  publicly_accessible = false

  tags = {
    Name        = "${var.instance_name}-database"
    Environment = var.environment
    Project     = "GitHub-Actions-Lightsail-Demo"
    ManagedBy   = "Terraform"
  }
}
