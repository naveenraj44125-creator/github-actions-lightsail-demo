# Lightsail QBR Application

A comprehensive web application for managing AWS Lightsail Quarterly Business Review (QBR) projects with role-based access control, voting, and commenting functionality.

![Lightsail QBR](https://img.shields.io/badge/AWS-Lightsail-FF9900?style=for-the-badge&logo=amazon-aws&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-563D7C?style=for-the-badge&logo=bootstrap&logoColor=white)

## 🚀 Features

### Core Functionality
- **Role-Based Authentication**: Admin and Employee user roles with different permissions
- **Project Management**: Create, view, edit, and delete QBR projects
- **Interactive Voting**: Upvote/downvote projects with real-time vote counting
- **Comment System**: Threaded discussions on projects with user engagement tracking
- **Responsive Design**: Mobile-friendly interface with AWS Lightsail branding

### Admin Features
- **User Management**: Create, edit, and manage user accounts
- **Project Administration**: Full CRUD operations for projects
- **Statistics Dashboard**: View engagement metrics and project analytics
- **Role Assignment**: Assign admin or employee roles to users

### Security Features
- **CSRF Protection**: Token-based protection against cross-site request forgery
- **Input Sanitization**: Comprehensive input validation and sanitization
- **Password Security**: Secure password hashing with PHP's password_hash()
- **Session Management**: Secure session handling with proper timeout

## 🛠 Technology Stack

- **Backend**: PHP 8+ with PDO for database operations
- **Database**: MySQL 8.0+ with proper foreign key relationships
- **Frontend**: Bootstrap 5, Custom CSS with AWS Lightsail theming
- **JavaScript**: Vanilla JS with modern ES6+ features
- **Security**: CSRF tokens, input sanitization, secure sessions

## 📋 Prerequisites

- AWS Lightsail instance with LAMP stack
- PHP 8.0 or higher
- MySQL 8.0 or higher
- Apache 2.4 or higher
- Modern web browser with JavaScript enabled

## 🚀 Quick Start

### 1. Clone the Repository

```bash
git clone <your-repository-url>
cd lightsail-qbr-app
```

### 2. Database Setup

```bash
# Connect to MySQL
mysql -u root -p

# Create database and user
CREATE DATABASE lightsail_qbr CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'qbr_user'@'localhost' IDENTIFIED BY 'qbr_password_2025';
GRANT ALL PRIVILEGES ON lightsail_qbr.* TO 'qbr_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Import database schema
mysql -u qbr_user -p lightsail_qbr < setup-database.sql
```

### 3. Configure Application

Edit `config/database.php` with your database credentials:

```php
$host = 'localhost';
$dbname = 'lightsail_qbr';
$username = 'qbr_user';
$password = 'qbr_password_2025';
```

### 4. Set Permissions

```bash
# Set proper file permissions
chmod -R 755 /path/to/lightsail-qbr-app
chmod -R 644 /path/to/lightsail-qbr-app/*.php
```

### 5. Access the Application

Navigate to your server's URL in a web browser and log in with the demo credentials:

- **Admin**: `admin` / `admin123`
- **Employee**: `employee` / `employee123`

## 📁 Project Structure

```
lightsail-qbr-app/
├── admin/                      # Admin panel pages
│   ├── add-project.php        # Project creation form
│   ├── manage-projects.php    # Project management interface
│   └── users.php              # User management interface
├── assets/                     # Static assets
│   ├── css/
│   │   └── style.css          # Custom CSS with AWS theming
│   └── js/
│       └── main.js            # Interactive JavaScript functionality
├── config/                     # Configuration files
│   └── database.php           # Database connection configuration
├── includes/                   # Shared PHP includes
│   └── auth.php               # Authentication and security functions
├── index.php                   # Main dashboard
├── login.php                   # User login page
├── register.php                # User registration page
├── logout.php                  # Logout handler
├── project-details.php         # Individual project view
├── setup-database.sql          # Database schema and sample data
├── DEPLOYMENT_GUIDE.md         # Comprehensive deployment guide
└── README.md                   # This file
```

## 🎨 User Interface

### Dashboard
- **Statistics Cards**: Display total projects, votes, and comments
- **Project Grid**: Responsive card layout with priority badges
- **Navigation**: Role-based navigation with admin-only sections

### Project Management
- **Voting Interface**: Interactive upvote/downvote buttons with animations
- **Comment System**: Threaded discussions with user attribution
- **Admin Controls**: Edit, delete, and status management for admins

### Admin Panel
- **User Management**: Complete user lifecycle management
- **Project Administration**: Full project CRUD operations
- **Analytics**: Engagement metrics and user statistics

## 🔐 Security Features

### Authentication & Authorization
- **Role-Based Access Control**: Admin and Employee roles with different permissions
- **Secure Password Handling**: PHP password_hash() and password_verify()
- **Session Security**: Secure session configuration with proper timeouts

### Input Security
- **CSRF Protection**: Token-based protection for all forms
- **Input Sanitization**: Comprehensive sanitization of user inputs
- **SQL Injection Prevention**: Prepared statements with PDO

### Data Protection
- **Foreign Key Constraints**: Database integrity with cascade operations
- **Input Validation**: Server-side validation for all user inputs
- **XSS Prevention**: Output escaping and content security policies

## 📊 Database Schema

### Users Table
- `id` (Primary Key)
- `username`, `email`, `password`
- `full_name`, `department`, `role`
- `created_at`, `updated_at`

### Projects Table
- `id` (Primary Key)
- `title`, `description`, `priority`, `status`
- `start_date`, `end_date`
- `created_by` (Foreign Key to Users)
- `created_at`, `updated_at`

### Votes Table
- `id` (Primary Key)
- `user_id` (Foreign Key to Users)
- `project_id` (Foreign Key to Projects)
- `vote_type` (up/down)
- `created_at`

### Comments Table
- `id` (Primary Key)
- `user_id` (Foreign Key to Users)
- `project_id` (Foreign Key to Projects)
- `comment_text`
- `created_at`

## 🎯 API Endpoints

### Authentication
- `POST /login.php` - User login
- `POST /register.php` - User registration
- `GET /logout.php` - User logout

### Projects
- `GET /index.php` - List all projects
- `GET /project-details.php?id={id}` - Get project details
- `POST /admin/add-project.php` - Create new project (Admin only)
- `POST /admin/manage-projects.php` - Update/delete projects (Admin only)

### User Management
- `GET /admin/users.php` - List all users (Admin only)
- `POST /admin/users.php` - Create/update/delete users (Admin only)

## 🚀 Deployment

For detailed deployment instructions, see [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md).

### Quick Deployment Steps

1. **AWS Lightsail Setup**: Create LAMP stack instance
2. **Database Configuration**: Set up MySQL database and user
3. **File Upload**: Deploy application files to web directory
4. **Permissions**: Set proper file and directory permissions
5. **SSL Setup**: Configure SSL certificate (recommended)
6. **Testing**: Verify all functionality works correctly

## 🔧 Configuration

### Environment Variables

The application uses the following configuration in `config/database.php`:

```php
$host = 'localhost';           # Database host
$dbname = 'lightsail_qbr';     # Database name
$username = 'qbr_user';        # Database username
$password = 'qbr_password_2025'; # Database password
```

### Security Configuration

Key security settings in `includes/auth.php`:

- **Session Timeout**: 30 minutes of inactivity
- **CSRF Token Expiry**: 1 hour
- **Password Requirements**: Minimum 6 characters
- **Input Sanitization**: HTML special characters escaped

## 🎨 Customization

### Theming

The application uses AWS Lightsail branding with customizable CSS variables in `assets/css/style.css`:

```css
:root {
    --lightsail-orange: #FF9900;
    --lightsail-blue: #232F3E;
    --lightsail-light-blue: #4A90E2;
    --gradient-primary: linear-gradient(135deg, #FF9900 0%, #FFB84D 100%);
    --gradient-secondary: linear-gradient(135deg, #232F3E 0%, #4A90E2 100%);
}
```

### Adding New Features

1. **Database Changes**: Update `setup-database.sql` with new schema
2. **Backend Logic**: Add new PHP files or modify existing ones
3. **Frontend Updates**: Update HTML, CSS, and JavaScript as needed
4. **Security Review**: Ensure new features follow security best practices

## 🧪 Testing

### Manual Testing Checklist

- [ ] User registration and login
- [ ] Role-based access control
- [ ] Project creation and management
- [ ] Voting functionality
- [ ] Comment system
- [ ] Admin panel operations
- [ ] Responsive design on mobile devices
- [ ] Security features (CSRF, input validation)

### Performance Testing

```bash
# Install Apache Bench
sudo apt install apache2-utils

# Test application performance
ab -n 100 -c 10 http://your-server-url/
```

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection Failed**
   - Check database credentials in `config/database.php`
   - Verify MySQL service is running
   - Ensure database and user exist

2. **Permission Denied Errors**
   - Check file permissions (755 for directories, 644 for files)
   - Verify web server user ownership

3. **Blank Pages or PHP Errors**
   - Check PHP error logs
   - Verify all required PHP extensions are installed
   - Enable error display temporarily for debugging

4. **CSS/JS Not Loading**
   - Check file paths in HTML
   - Verify web server can serve static files
   - Check browser developer tools for 404 errors

### Debug Mode

To enable debug mode, temporarily modify `config/database.php`:

```php
// Add at the top of the file
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

**Remember to disable debug mode in production!**

## 📈 Performance Optimization

### Database Optimization
- **Indexes**: Proper indexing on frequently queried columns
- **Query Optimization**: Efficient SQL queries with proper JOINs
- **Connection Pooling**: Reuse database connections

### Frontend Optimization
- **CSS Minification**: Minify CSS for production
- **JavaScript Optimization**: Minimize and compress JS files
- **Image Optimization**: Optimize images for web delivery
- **Caching**: Implement browser caching headers

### Server Optimization
- **PHP OPcache**: Enable PHP bytecode caching
- **Apache Compression**: Enable gzip compression
- **CDN**: Use CDN for static assets (optional)

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Guidelines

- Follow PSR-12 coding standards for PHP
- Use meaningful variable and function names
- Add comments for complex logic
- Test all changes thoroughly
- Update documentation as needed

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- **AWS Lightsail** for providing the hosting platform
- **Bootstrap** for the responsive UI framework
- **Font Awesome** for the icon library
- **PHP Community** for excellent documentation and resources

## 📞 Support

For support and questions:

1. Check the [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md) for deployment issues
2. Review the troubleshooting section above
3. Check the [Issues](../../issues) page for known problems
4. Create a new issue if your problem isn't covered

## 🗺 Roadmap

### Planned Features

- [ ] **Email Notifications**: Notify users of new projects and comments
- [ ] **Advanced Analytics**: Detailed reporting and analytics dashboard
- [ ] **File Attachments**: Allow file uploads for projects
- [ ] **API Integration**: RESTful API for mobile app integration
- [ ] **Advanced Search**: Full-text search across projects and comments
- [ ] **User Profiles**: Extended user profiles with avatars
- [ ] **Project Categories**: Organize projects by categories/tags
- [ ] **Audit Logging**: Comprehensive audit trail for admin actions

### Version History

- **v1.0.0** - Initial release with core functionality
  - User authentication and role management
  - Project management with voting and comments
  - Admin panel with user and project management
  - Responsive design with AWS Lightsail theming

---

**Built with ❤️ for AWS Lightsail Quarterly Business Reviews**

For more information about AWS Lightsail, visit [https://aws.amazon.com/lightsail/](https://aws.amazon.com/lightsail/)
