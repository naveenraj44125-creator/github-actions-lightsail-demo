variable "aws_region" {
  description = "AWS region for Lightsail resources"
  type        = string
  default     = "us-east-1"
}

variable "instance_name" {
  description = "Name for the Lightsail instance"
  type        = string
  default     = "my-app-instance"
}

variable "blueprint_id" {
  description = "Lightsail blueprint ID (OS image)"
  type        = string
  default     = "ubuntu_20_04"
  
  validation {
    condition = contains([
      "ubuntu_20_04",
      "ubuntu_22_04",
      "amazon_linux_2",
      "centos_7_2009_01",
      "debian_11"
    ], var.blueprint_id)
    error_message = "Blueprint ID must be a valid Lightsail blueprint."
  }
}

variable "bundle_id" {
  description = "Lightsail bundle ID (instance size)"
  type        = string
  default     = "nano_2_0"
  
  validation {
    condition = contains([
      "nano_2_0",
      "micro_2_0",
      "small_2_0",
      "medium_2_0",
      "large_2_0",
      "xlarge_2_0",
      "2xlarge_2_0"
    ], var.bundle_id)
    error_message = "Bundle ID must be a valid Lightsail bundle."
  }
}

variable "environment" {
  description = "Environment name (dev, staging, prod)"
  type        = string
  default     = "dev"
  
  validation {
    condition     = contains(["dev", "staging", "prod"], var.environment)
    error_message = "Environment must be dev, staging, or prod."
  }
}

variable "app_name" {
  description = "Application name"
  type        = string
  default     = "lightsail-demo-app"
}

variable "create_container_service" {
  description = "Whether to create a Lightsail Container Service"
  type        = bool
  default     = false
}

variable "create_database" {
  description = "Whether to create a Lightsail database"
  type        = bool
  default     = false
}

variable "db_name" {
  description = "Database name"
  type        = string
  default     = "appdb"
}

variable "db_username" {
  description = "Database master username"
  type        = string
  default     = "admin"
}

variable "db_password" {
  description = "Database master password"
  type        = string
  sensitive   = true
  default     = "ChangeMe123!"
  
  validation {
    condition     = length(var.db_password) >= 8
    error_message = "Database password must be at least 8 characters long."
  }
}

variable "allowed_cidr_blocks" {
  description = "CIDR blocks allowed to access the instance"
  type        = list(string)
  default     = ["0.0.0.0/0"]
}

variable "enable_monitoring" {
  description = "Enable CloudWatch monitoring"
  type        = bool
  default     = true
}

variable "backup_retention_days" {
  description = "Number of days to retain backups"
  type        = number
  default     = 7
  
  validation {
    condition     = var.backup_retention_days >= 1 && var.backup_retention_days <= 35
    error_message = "Backup retention days must be between 1 and 35."
  }
}
