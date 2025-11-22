# SplashLMS - Multi-Tenant Learning Management System

A complete, production-ready multi-tenant SaaS Learning Management System built with pure PHP and MySQL. No frameworks required - just clean, scalable MVC architecture.

## Features

### Core Functionality
- **Multi-Tenant Architecture**: Each organization/academy gets isolated workspace
- **Course Management**: Create courses with sections, lessons (video/text/download)
- **Quiz System**: Multiple-choice quizzes with automatic grading
- **Certificate Generation**: Automatic certificates upon course completion
- **Student Portal**: Enroll in courses, track progress, take quizzes
- **Progress Tracking**: Real-time progress updates and completion tracking
- **File Uploads**: Secure file upload with validation and storage management

### SaaS Features
- **Subscription Plans**: Multiple pricing tiers with quota limits
- **Quota Enforcement**: Limits on courses, students, enrollments, storage
- **Billing System**: Invoices and payment tracking (simulated payments)
- **Usage Analytics**: Track resource usage per tenant
- **API Integration**: RESTful API for course catalog and enrollments

### Security
- **CSRF Protection**: All forms protected against CSRF attacks
- **Password Hashing**: Secure password_hash() for all passwords
- **Input Validation**: Comprehensive validation and sanitization
- **SQL Injection Prevention**: PDO prepared statements only
- **XSS Prevention**: All output escaped with htmlspecialchars()
- **Role-Based Access Control**: Platform admin, tenant admin, instructor, student roles

## Requirements

- PHP 7.0 or higher
- MySQL 5.7+ or MariaDB 10.2+
- Apache with mod_rewrite enabled (or Nginx)
- PHP Extensions:
  - PDO
  - pdo_mysql
  - mbstring
  - json

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/yourusername/SplashLMS.git
cd SplashLMS
```

### 2. Configure Environment

```bash
cp .env.example .env
```

Edit `.env` with your database credentials:

```env
APP_NAME=SplashLMS
APP_ENV=development
APP_URL=http://localhost
APP_TIMEZONE=UTC

DB_HOST=localhost
DB_NAME=splashlms
DB_USER=root
DB_PASS=your_password
```

### 3. Create Database

```bash
mysql -u root -p
```

```sql
CREATE DATABASE splashlms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;
```

### 4. Import Database Schema

```bash
mysql -u root -p splashlms < database.sql
```

This will create all tables and seed demo data including:
- 2 sample tenants (Tech Academy, Language School)
- Sample users for each role
- Sample courses with lessons and quizzes
- Sample enrollments and certificates

### 5. Set Permissions

```bash
chmod -R 755 storage/
chmod -R 755 storage/uploads/
```

### 6. Configure Web Server

#### Apache (.htaccess included)

Point document root to `/public` directory:

```apache
<VirtualHost *:80>
    ServerName splashlms.local
    DocumentRoot /path/to/SplashLMS/public

    <Directory /path/to/SplashLMS/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/splashlms_error.log
    CustomLog ${APACHE_LOG_DIR}/splashlms_access.log combined
</VirtualHost>
```

#### Nginx

```nginx
server {
    listen 80;
    server_name splashlms.local;
    root /path/to/SplashLMS/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 7. Access the Application

Visit: `http://localhost` or your configured domain

## Default Credentials

### Platform Admin
- Email: `admin@splashlms.com`
- Password: `password123`
- Tenant: Leave blank

### Tech Academy (Tenant Admin)
- Email: `admin@techacademy.example`
- Password: `password123`
- Tenant Slug: `tech-academy`

### Language School (Tenant Admin)
- Email: `admin@languageschool.example`
- Password: `password123`
- Tenant Slug: `language-school`

### Sample Student
- Email: `student1@example.com`
- Password: `password123`
- Tenant Slug: `tech-academy`

## API Documentation

### Authentication

All API requests require an API key in the `X-API-KEY` header.

Get your API key from the database:
```sql
SELECT api_key FROM tenants WHERE slug = 'your-slug';
```

### Endpoints

#### 1. Get Course Catalog

```http
GET /api/catalog
X-API-KEY: your_api_key
```

**Query Parameters:**
- `page` (optional): Page number (default: 1)
- `per_page` (optional): Items per page (default: 20, max: 100)
- `category_id` (optional): Filter by category
- `level` (optional): Filter by level (beginner, intermediate, advanced)
- `search` (optional): Search in title and description

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Complete Web Development Bootcamp",
      "slug": "complete-web-development-bootcamp",
      "short_description": "Learn HTML, CSS, JavaScript...",
      "category": "Web Development",
      "level": "beginner",
      "language": "en",
      "price": 0.00,
      "currency": "USD",
      "thumbnail": null,
      "instructor_name": "John Doe",
      "enrollments_count": 3,
      "published_at": "2025-01-01 12:00:00"
    }
  ],
  "pagination": {
    "total": 5,
    "per_page": 20,
    "current_page": 1,
    "total_pages": 1
  }
}
```

#### 2. Get Course Details

```http
GET /api/course/{id}
X-API-KEY: your_api_key
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Complete Web Development Bootcamp",
    "slug": "complete-web-development-bootcamp",
    "description": "<p>Full course description...</p>",
    "category": "Web Development",
    "level": "beginner",
    "price": 0.00,
    "instructor": {
      "name": "John Doe",
      "bio": "Experienced developer..."
    },
    "sections": [
      {
        "id": 1,
        "title": "Getting Started",
        "description": "Introduction to web development",
        "lessons": [
          {
            "id": 1,
            "title": "Welcome to the Course",
            "type": "video",
            "duration_minutes": 10,
            "is_preview": true
          }
        ]
      }
    ]
  }
}
```

#### 3. Enroll Student

```http
POST /api/enroll
X-API-KEY: your_api_key
Content-Type: application/json
```

**Request Body:**
```json
{
  "course_id": 1,
  "student_email": "newstudent@example.com",
  "student_name": "John Smith"
}
```

If the student exists, only `course_id` and `student_email` are required. For new students, `student_name` is also required.

**Response:**
```json
{
  "success": true,
  "data": {
    "enrollment_id": 123,
    "course_id": 1,
    "student_id": 45,
    "status": "active"
  }
}
```

**Error Response:**
```json
{
  "success": false,
  "error": "Student is already enrolled in this course"
}
```

### API Error Codes

- `400` - Bad Request (invalid parameters)
- `401` - Unauthorized (invalid or missing API key)
- `404` - Not Found (course/resource not found)
- `405` - Method Not Allowed
- `500` - Internal Server Error

## Project Structure

```
SplashLMS/
├── app/
│   ├── controllers/      # All controllers
│   ├── models/          # All models
│   ├── views/           # All views
│   ├── core/            # Core MVC framework
│   └── helpers/         # Helper functions
├── config/
│   ├── app.php          # App configuration
│   └── routes.php       # Route definitions
├── public/
│   ├── index.php        # Entry point
│   ├── .htaccess        # Apache rewrite rules
│   └── assets/          # CSS, JS, images
├── storage/
│   └── uploads/         # Uploaded files
├── tests/               # Test files
├── database.sql         # Database schema & seed data
├── .env.example         # Environment template
└── README.md           # This file
```

## User Roles & Permissions

### Platform Admin
- Manage all tenants
- View platform-wide statistics
- Create/edit/delete tenants
- Manage subscription plans

### Tenant Admin
- Full access to their tenant
- Manage courses, students, instructors
- View analytics and billing
- Manage organization settings

### Instructor
- Create and manage their own courses
- View enrollments for their courses
- Create quizzes and lessons
- View course analytics

### Student
- Browse course catalog
- Enroll in courses
- Access course content
- Take quizzes
- Track progress
- Download certificates

## Subscription Plans

The system includes 4 default plans:

1. **Free Trial** - $0/month
   - 3 courses, 50 students, 100 enrollments, 500MB storage

2. **Starter** - $29/month
   - 10 courses, 200 students, 500 enrollments, 2GB storage

3. **Professional** - $79/month
   - 50 courses, 1000 students, 5000 enrollments, 10GB storage

4. **Enterprise** - $199/month
   - 999 courses, 10000 students, 100000 enrollments, 100GB storage

Quota enforcement is automatic. When limits are reached, clear error messages are displayed.

## Testing

### Manual Testing Checklist

#### Authentication
- [ ] Platform admin can login
- [ ] Tenant admin can login with tenant slug
- [ ] Students can login with tenant slug
- [ ] Registration creates new tenant and admin user
- [ ] Logout works correctly

#### Course Management
- [ ] Create course with sections and lessons
- [ ] Add video, text, and download lessons
- [ ] Create quiz with questions
- [ ] Publish/unpublish courses
- [ ] Delete courses

#### Student Enrollment
- [ ] Students can browse course catalog
- [ ] Students can enroll in free courses
- [ ] Students can enroll in paid courses (simulated payment)
- [ ] Enrollment respects subscription limits

#### Learning Experience
- [ ] Students can view course content
- [ ] Video lessons play correctly
- [ ] Text lessons display properly
- [ ] Mark lesson as complete updates progress
- [ ] Quiz submission calculates score correctly
- [ ] Certificate generates on course completion

#### Multi-Tenancy
- [ ] Users only see data from their tenant
- [ ] API only returns tenant's courses
- [ ] Cross-tenant access is prevented

#### Quota Enforcement
- [ ] Course creation blocked when limit reached
- [ ] Student creation blocked when limit reached
- [ ] Enrollment blocked when limit reached
- [ ] Storage upload blocked when limit reached

#### API Integration
- [ ] GET /api/catalog returns published courses
- [ ] GET /api/course/{id} returns course details
- [ ] POST /api/enroll creates enrollment
- [ ] Invalid API key returns 401
- [ ] API respects tenant isolation

### Automated Tests

Run basic functional tests:

```bash
php tests/run_tests.php
```

## Security Best Practices

1. **Change Default Passwords**: Update all default passwords immediately
2. **Use HTTPS**: Always use SSL/TLS in production
3. **Update .env**: Never commit .env file to version control
4. **File Permissions**: Set appropriate permissions on storage directories
5. **Regular Backups**: Implement automated database backups
6. **Keep Updated**: Regularly update PHP and MySQL
7. **Monitor Logs**: Check error logs regularly
8. **Rate Limiting**: Implement rate limiting for API endpoints in production

## Performance Optimization

1. **Enable OPcache**: Configure PHP OPcache for better performance
2. **Database Indexing**: All foreign keys and common queries are indexed
3. **Pagination**: All list views use pagination
4. **Query Optimization**: Use EXPLAIN to optimize slow queries
5. **CDN**: Serve static assets via CDN in production
6. **Caching**: Implement Redis/Memcached for session storage

## Deployment Guide

### Production Checklist

1. Set `APP_ENV=production` in `.env`
2. Use strong, unique passwords for all accounts
3. Configure HTTPS with valid SSL certificate
4. Set up automated database backups
5. Configure error logging (not displayed to users)
6. Enable PHP OPcache
7. Set restrictive file permissions
8. Configure firewall rules
9. Set up monitoring and alerts
10. Implement rate limiting for API

### Environment Variables for Production

```env
APP_ENV=production
APP_URL=https://yourdomain.com
DB_HOST=your-db-host
DB_NAME=splashlms
DB_USER=splashlms_user
DB_PASS=strong_password_here
```

## Troubleshooting

### Common Issues

**Issue**: 404 on all pages
- **Solution**: Enable mod_rewrite on Apache or check Nginx configuration

**Issue**: Database connection failed
- **Solution**: Check .env database credentials and ensure MySQL is running

**Issue**: File upload fails
- **Solution**: Check storage/uploads permissions (755 or 777)

**Issue**: Blank page/white screen
- **Solution**: Check PHP error logs, ensure all required extensions are installed

**Issue**: CSRF token mismatch
- **Solution**: Ensure sessions are working, check session save path permissions

## Contributing

This is a complete SaaS project. To contribute:

1. Fork the repository
2. Create a feature branch
3. Make your changes with clear commit messages
4. Write tests for new features
5. Submit a pull request

## License

This project is open-source and available for educational purposes.

## Support

For issues and questions:
- Create an issue on GitHub
- Check existing documentation
- Review code comments

## Credits

Built with pure PHP, MySQL, and vanilla JavaScript. No frameworks, no dependencies - just clean, scalable code.

---

**SplashLMS** - Complete Multi-Tenant LMS
Version 1.0.0
