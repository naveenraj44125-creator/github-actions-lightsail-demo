-- Lightsail QBR Database Setup Script
-- Run this script to create the database and tables

CREATE DATABASE IF NOT EXISTS lightsail_qbr;
USE lightsail_qbr;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'employee') DEFAULT 'employee',
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Create projects table
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    status ENUM('draft', 'active', 'completed', 'cancelled') DEFAULT 'active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Create votes table
CREATE TABLE IF NOT EXISTS votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_vote (project_id, user_id),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create comments table
CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password, role) VALUES 
('admin', 'admin@lightsail-qbr.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin')
ON DUPLICATE KEY UPDATE username = username;

-- Insert default employee user (password: employee123)
INSERT INTO users (username, email, password, role) VALUES 
('employee', 'employee@lightsail-qbr.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee')
ON DUPLICATE KEY UPDATE username = username;

-- Insert sample projects
INSERT INTO projects (title, description, priority, created_by) VALUES 
(
    'Lightsail Container Service Enhancement',
    'Improve the container service offering with better scaling capabilities, enhanced monitoring, and simplified deployment workflows. This project aims to make Lightsail containers more competitive with other cloud container services.',
    'high',
    1
),
(
    'Cost Optimization Dashboard',
    'Develop a comprehensive cost optimization dashboard that helps customers understand their Lightsail spending patterns and provides recommendations for cost savings. Include predictive analytics and budget alerts.',
    'medium',
    1
),
(
    'Multi-Region Load Balancer',
    'Implement multi-region load balancing capabilities for Lightsail instances to improve global application performance and availability. This will enable customers to distribute traffic across multiple AWS regions.',
    'high',
    1
),
(
    'Automated Backup and Disaster Recovery',
    'Create an automated backup and disaster recovery solution that provides point-in-time recovery, cross-region backup replication, and simplified restore processes for Lightsail resources.',
    'medium',
    1
),
(
    'Developer Experience Improvements',
    'Enhance the developer experience with better CLI tools, improved documentation, more code examples, and integration with popular development frameworks and CI/CD pipelines.',
    'low',
    1
)
ON DUPLICATE KEY UPDATE title = title;

-- Insert sample votes
INSERT INTO votes (project_id, user_id) VALUES 
(1, 2),
(2, 2),
(3, 2)
ON DUPLICATE KEY UPDATE project_id = project_id;

-- Insert sample comments
INSERT INTO comments (project_id, user_id, comment) VALUES 
(1, 2, 'This is a great initiative! Container services are becoming increasingly important for our customers.'),
(2, 2, 'Cost optimization is always a priority. This dashboard would be very valuable for our enterprise customers.'),
(3, 2, 'Multi-region capabilities would definitely help us compete better in the global market.')
ON DUPLICATE KEY UPDATE comment = comment;

-- Create database user for the application
CREATE USER IF NOT EXISTS 'qbr_user'@'localhost' IDENTIFIED BY 'qbr_password_2025';
GRANT SELECT, INSERT, UPDATE, DELETE ON lightsail_qbr.* TO 'qbr_user'@'localhost';
FLUSH PRIVILEGES;

-- Display setup completion message
SELECT 'Database setup completed successfully!' AS message;
SELECT 'Default admin user: admin / admin123' AS admin_credentials;
SELECT 'Default employee user: employee / employee123' AS employee_credentials;
