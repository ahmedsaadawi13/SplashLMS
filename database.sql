-- FILE: /database.sql
-- SplashLMS Multi-Tenant SaaS Database Schema
-- Compatible with MySQL 5.7+ and MariaDB 10.2+

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Create database
CREATE DATABASE IF NOT EXISTS splashlms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE splashlms;

-- ========================================
-- SUBSCRIPTION & BILLING TABLES
-- ========================================

-- Subscription plans define limits and pricing
CREATE TABLE subscription_plans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    currency VARCHAR(3) NOT NULL DEFAULT 'USD',
    billing_cycle ENUM('monthly', 'yearly', 'lifetime') NOT NULL DEFAULT 'monthly',
    max_courses INT UNSIGNED NOT NULL DEFAULT 10,
    max_students INT UNSIGNED NOT NULL DEFAULT 100,
    max_enrollments INT UNSIGNED NOT NULL DEFAULT 1000,
    max_storage_mb INT UNSIGNED NOT NULL DEFAULT 1000,
    features JSON,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TENANT TABLES
-- ========================================

-- Tenants represent organizations/academies/individual instructors
CREATE TABLE tenants (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    logo VARCHAR(255),
    description TEXT,
    website VARCHAR(255),
    contact_email VARCHAR(255),
    contact_phone VARCHAR(50),
    address TEXT,
    default_language VARCHAR(10) DEFAULT 'en',
    default_currency VARCHAR(3) DEFAULT 'USD',
    timezone VARCHAR(50) DEFAULT 'UTC',
    api_key VARCHAR(64) UNIQUE,
    status ENUM('active', 'suspended', 'canceled') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_api_key (api_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tenant subscriptions
CREATE TABLE tenant_subscriptions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    plan_id INT UNSIGNED NOT NULL,
    status ENUM('trialing', 'active', 'past_due', 'canceled', 'expired') NOT NULL DEFAULT 'trialing',
    trial_ends_at TIMESTAMP NULL,
    current_period_start TIMESTAMP NOT NULL,
    current_period_end TIMESTAMP NOT NULL,
    canceled_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES subscription_plans(id),
    INDEX idx_tenant (tenant_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usage tracking for quota enforcement
CREATE TABLE tenant_usage (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    courses_count INT UNSIGNED DEFAULT 0,
    students_count INT UNSIGNED DEFAULT 0,
    enrollments_count INT UNSIGNED DEFAULT 0,
    storage_used_mb INT UNSIGNED DEFAULT 0,
    last_calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY uk_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Invoices
CREATE TABLE invoices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    subscription_id INT UNSIGNED,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'USD',
    status ENUM('draft', 'pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
    due_date DATE,
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (subscription_id) REFERENCES tenant_subscriptions(id) ON DELETE SET NULL,
    INDEX idx_tenant (tenant_id),
    INDEX idx_status (status),
    INDEX idx_invoice_number (invoice_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payments
CREATE TABLE payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    invoice_id INT UNSIGNED,
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'USD',
    payment_method VARCHAR(50),
    transaction_id VARCHAR(100),
    status ENUM('pending', 'completed', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL,
    INDEX idx_tenant (tenant_id),
    INDEX idx_invoice (invoice_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- USER & AUTH TABLES
-- ========================================

-- Users (platform admins, tenant admins, instructors, students)
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(200) NOT NULL,
    role ENUM('platform_admin', 'tenant_admin', 'instructor', 'student') NOT NULL,
    avatar VARCHAR(255),
    bio TEXT,
    country VARCHAR(100),
    timezone VARCHAR(50) DEFAULT 'UTC',
    language VARCHAR(10) DEFAULT 'en',
    status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    email_verified_at TIMESTAMP NULL,
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY uk_email_tenant (email, tenant_id),
    INDEX idx_tenant (tenant_id),
    INDEX idx_role (role),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- COURSE TABLES
-- ========================================

-- Course categories
CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    description TEXT,
    parent_id INT UNSIGNED,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
    UNIQUE KEY uk_slug_tenant (slug, tenant_id),
    INDEX idx_tenant (tenant_id),
    INDEX idx_parent (parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Courses
CREATE TABLE courses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    instructor_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    short_description TEXT,
    description TEXT,
    thumbnail VARCHAR(255),
    level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    language VARCHAR(10) DEFAULT 'en',
    price DECIMAL(10, 2) DEFAULT 0.00,
    currency VARCHAR(3) DEFAULT 'USD',
    status ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
    visibility ENUM('public', 'private', 'unlisted') NOT NULL DEFAULT 'public',
    is_featured TINYINT(1) DEFAULT 0,
    duration_minutes INT UNSIGNED DEFAULT 0,
    prerequisite_course_id INT UNSIGNED,
    drip_enabled TINYINT(1) DEFAULT 0,
    drip_days_interval INT UNSIGNED DEFAULT 1,
    certificate_enabled TINYINT(1) DEFAULT 1,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (prerequisite_course_id) REFERENCES courses(id) ON DELETE SET NULL,
    UNIQUE KEY uk_slug_tenant (slug, tenant_id),
    INDEX idx_tenant (tenant_id),
    INDEX idx_instructor (instructor_id),
    INDEX idx_category (category_id),
    INDEX idx_status (status),
    INDEX idx_visibility (visibility)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Course sections/modules
CREATE TABLE course_sections (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    INDEX idx_course (course_id),
    INDEX idx_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lessons within sections
CREATE TABLE lessons (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED NOT NULL,
    section_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    type ENUM('video', 'text', 'download', 'quiz') NOT NULL DEFAULT 'text',
    content TEXT,
    video_url VARCHAR(500),
    attachment VARCHAR(255),
    duration_minutes INT UNSIGNED DEFAULT 0,
    display_order INT DEFAULT 0,
    is_preview TINYINT(1) DEFAULT 0,
    is_published TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (section_id) REFERENCES course_sections(id) ON DELETE CASCADE,
    INDEX idx_course (course_id),
    INDEX idx_section (section_id),
    INDEX idx_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- ENROLLMENT & PROGRESS TABLES
-- ========================================

-- Student enrollments
CREATE TABLE enrollments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    enrollment_type ENUM('free', 'paid') NOT NULL DEFAULT 'free',
    payment_id INT UNSIGNED,
    status ENUM('active', 'completed', 'expired', 'refunded') NOT NULL DEFAULT 'active',
    progress_percent DECIMAL(5, 2) DEFAULT 0.00,
    completed_lessons INT UNSIGNED DEFAULT 0,
    total_lessons INT UNSIGNED DEFAULT 0,
    last_accessed_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE SET NULL,
    UNIQUE KEY uk_student_course (student_id, course_id),
    INDEX idx_tenant (tenant_id),
    INDEX idx_student (student_id),
    INDEX idx_course (course_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lesson progress tracking
CREATE TABLE lesson_progress (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT UNSIGNED NOT NULL,
    lesson_id INT UNSIGNED NOT NULL,
    status ENUM('not_started', 'in_progress', 'completed') NOT NULL DEFAULT 'not_started',
    completed_at TIMESTAMP NULL,
    last_accessed_at TIMESTAMP NULL,
    time_spent_seconds INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    UNIQUE KEY uk_enrollment_lesson (enrollment_id, lesson_id),
    INDEX idx_enrollment (enrollment_id),
    INDEX idx_lesson (lesson_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- QUIZ & ASSESSMENT TABLES
-- ========================================

-- Quizzes
CREATE TABLE quizzes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    lesson_id INT UNSIGNED,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    passing_score DECIMAL(5, 2) DEFAULT 70.00,
    time_limit_minutes INT UNSIGNED,
    max_attempts INT UNSIGNED DEFAULT 0,
    show_correct_answers TINYINT(1) DEFAULT 1,
    randomize_questions TINYINT(1) DEFAULT 0,
    is_required TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    INDEX idx_tenant (tenant_id),
    INDEX idx_course (course_id),
    INDEX idx_lesson (lesson_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Quiz questions
CREATE TABLE quiz_questions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT UNSIGNED NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('multiple_choice', 'true_false') NOT NULL DEFAULT 'multiple_choice',
    options JSON NOT NULL,
    correct_answer VARCHAR(10) NOT NULL,
    points DECIMAL(5, 2) DEFAULT 1.00,
    explanation TEXT,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    INDEX idx_quiz (quiz_id),
    INDEX idx_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Quiz attempts
CREATE TABLE quiz_attempts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    enrollment_id INT UNSIGNED NOT NULL,
    score DECIMAL(5, 2) DEFAULT 0.00,
    max_score DECIMAL(5, 2) DEFAULT 100.00,
    percentage DECIMAL(5, 2) DEFAULT 0.00,
    passed TINYINT(1) DEFAULT 0,
    answers JSON,
    time_spent_seconds INT UNSIGNED,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
    INDEX idx_quiz (quiz_id),
    INDEX idx_student (student_id),
    INDEX idx_enrollment (enrollment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- CERTIFICATE TABLES
-- ========================================

-- Certificates
CREATE TABLE certificates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    enrollment_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    certificate_number VARCHAR(100) UNIQUE NOT NULL,
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY uk_enrollment (enrollment_id),
    INDEX idx_tenant (tenant_id),
    INDEX idx_student (student_id),
    INDEX idx_course (course_id),
    INDEX idx_certificate_number (certificate_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- NOTIFICATION TABLES
-- ========================================

-- Notification log (simulated emails)
CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    type VARCHAR(50) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_tenant (tenant_id),
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- SEED DATA
-- ========================================

-- Insert subscription plans
INSERT INTO subscription_plans (name, description, price, currency, billing_cycle, max_courses, max_students, max_enrollments, max_storage_mb, features) VALUES
('Free Trial', 'Perfect for getting started', 0.00, 'USD', 'monthly', 3, 50, 100, 500, '["Basic Support", "Community Access"]'),
('Starter', 'For small academies and individual instructors', 29.00, 'USD', 'monthly', 10, 200, 500, 2000, '["Email Support", "Custom Domain", "Remove Branding"]'),
('Professional', 'For growing organizations', 79.00, 'USD', 'monthly', 50, 1000, 5000, 10000, '["Priority Support", "Advanced Analytics", "White Label", "API Access"]'),
('Enterprise', 'For large organizations', 199.00, 'USD', 'monthly', 999, 10000, 100000, 100000, '["24/7 Support", "Custom Integrations", "Dedicated Account Manager", "SLA Guarantee"]');

-- Insert tenants
INSERT INTO tenants (name, slug, logo, description, website, contact_email, contact_phone, default_language, default_currency, api_key, status) VALUES
('Tech Academy', 'tech-academy', NULL, 'Leading online technology training platform', 'https://techacademy.example', 'info@techacademy.example', '+1-555-0101', 'en', 'USD', 'TA_' || SUBSTR(MD5(RAND()), 1, 40), 'active'),
('Language School', 'language-school', NULL, 'Learn languages online with native speakers', 'https://languageschool.example', 'hello@languageschool.example', '+1-555-0102', 'en', 'USD', 'LS_' || SUBSTR(MD5(RAND()), 1, 40), 'active');

-- Get tenant IDs for foreign keys
SET @tech_academy_id = (SELECT id FROM tenants WHERE slug = 'tech-academy');
SET @language_school_id = (SELECT id FROM tenants WHERE slug = 'language-school');

-- Insert tenant subscriptions
INSERT INTO tenant_subscriptions (tenant_id, plan_id, status, trial_ends_at, current_period_start, current_period_end) VALUES
(@tech_academy_id, 3, 'active', NULL, NOW(), DATE_ADD(NOW(), INTERVAL 1 MONTH)),
(@language_school_id, 2, 'active', NULL, NOW(), DATE_ADD(NOW(), INTERVAL 1 MONTH));

-- Initialize tenant usage
INSERT INTO tenant_usage (tenant_id, courses_count, students_count, enrollments_count, storage_used_mb) VALUES
(@tech_academy_id, 0, 0, 0, 0),
(@language_school_id, 0, 0, 0, 0);

-- Insert users
-- Password for all users: password123 (hashed with PASSWORD_DEFAULT)
INSERT INTO users (tenant_id, email, password_hash, name, role, status, email_verified_at) VALUES
(NULL, 'admin@splashlms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Platform Admin', 'platform_admin', 'active', NOW()),
(@tech_academy_id, 'admin@techacademy.example', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Tech Academy Admin', 'tenant_admin', 'active', NOW()),
(@tech_academy_id, 'john.doe@techacademy.example', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Doe', 'instructor', 'active', NOW()),
(@tech_academy_id, 'jane.smith@techacademy.example', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane Smith', 'instructor', 'active', NOW()),
(@tech_academy_id, 'student1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Alice Johnson', 'student', 'active', NOW()),
(@tech_academy_id, 'student2@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Bob Williams', 'student', 'active', NOW()),
(@tech_academy_id, 'student3@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Charlie Brown', 'student', 'active', NOW()),
(@language_school_id, 'admin@languageschool.example', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Language School Admin', 'tenant_admin', 'active', NOW()),
(@language_school_id, 'maria.garcia@languageschool.example', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Maria Garcia', 'instructor', 'active', NOW()),
(@language_school_id, 'student4@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'David Lee', 'student', 'active', NOW()),
(@language_school_id, 'student5@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Emma Davis', 'student', 'active', NOW());

-- Get user IDs
SET @tech_instructor_1 = (SELECT id FROM users WHERE email = 'john.doe@techacademy.example');
SET @tech_instructor_2 = (SELECT id FROM users WHERE email = 'jane.smith@techacademy.example');
SET @lang_instructor_1 = (SELECT id FROM users WHERE email = 'maria.garcia@languageschool.example');
SET @tech_student_1 = (SELECT id FROM users WHERE email = 'student1@example.com');
SET @tech_student_2 = (SELECT id FROM users WHERE email = 'student2@example.com');
SET @tech_student_3 = (SELECT id FROM users WHERE email = 'student3@example.com');
SET @lang_student_1 = (SELECT id FROM users WHERE email = 'student4@example.com');
SET @lang_student_2 = (SELECT id FROM users WHERE email = 'student5@example.com');

-- Insert categories for Tech Academy
INSERT INTO categories (tenant_id, name, slug, description, display_order) VALUES
(@tech_academy_id, 'Web Development', 'web-development', 'Learn web development from basics to advanced', 1),
(@tech_academy_id, 'Mobile Development', 'mobile-development', 'Build mobile apps for iOS and Android', 2),
(@tech_academy_id, 'Data Science', 'data-science', 'Master data analysis and machine learning', 3),
(@tech_academy_id, 'DevOps', 'devops', 'Learn DevOps tools and practices', 4);

-- Insert categories for Language School
INSERT INTO categories (tenant_id, name, slug, description, display_order) VALUES
(@language_school_id, 'Spanish', 'spanish', 'Learn Spanish from beginner to advanced', 1),
(@language_school_id, 'French', 'french', 'Master French language skills', 2),
(@language_school_id, 'German', 'german', 'Learn German effectively', 3);

-- Get category IDs
SET @cat_web_dev = (SELECT id FROM categories WHERE slug = 'web-development' AND tenant_id = @tech_academy_id);
SET @cat_mobile = (SELECT id FROM categories WHERE slug = 'mobile-development' AND tenant_id = @tech_academy_id);
SET @cat_data_science = (SELECT id FROM categories WHERE slug = 'data-science' AND tenant_id = @tech_academy_id);
SET @cat_spanish = (SELECT id FROM categories WHERE slug = 'spanish' AND tenant_id = @language_school_id);
SET @cat_french = (SELECT id FROM categories WHERE slug = 'french' AND tenant_id = @language_school_id);

-- Insert courses for Tech Academy
INSERT INTO courses (tenant_id, instructor_id, category_id, title, slug, short_description, description, level, price, status, visibility, is_featured, certificate_enabled, published_at) VALUES
(@tech_academy_id, @tech_instructor_1, @cat_web_dev, 'Complete Web Development Bootcamp', 'complete-web-development-bootcamp', 'Learn HTML, CSS, JavaScript, PHP and MySQL from scratch', '<p>This comprehensive course covers everything you need to become a full-stack web developer. You will learn HTML5, CSS3, JavaScript, PHP, MySQL, and modern web development practices.</p><p>Perfect for beginners with no prior experience!</p>', 'beginner', 0.00, 'published', 'public', 1, 1, NOW()),
(@tech_academy_id, @tech_instructor_1, @cat_web_dev, 'Advanced PHP & MySQL Development', 'advanced-php-mysql-development', 'Master advanced PHP techniques and database optimization', '<p>Take your PHP skills to the next level with advanced OOP, design patterns, performance optimization, and security best practices.</p>', 'advanced', 49.99, 'published', 'public', 1, 1, NOW()),
(@tech_academy_id, @tech_instructor_2, @cat_mobile, 'iOS App Development with Swift', 'ios-app-development-swift', 'Build beautiful iOS apps from scratch', '<p>Learn to build native iOS applications using Swift and SwiftUI. This course covers UI design, data persistence, networking, and App Store deployment.</p>', 'intermediate', 79.99, 'published', 'public', 0, 1, NOW()),
(@tech_academy_id, @tech_instructor_2, @cat_data_science, 'Data Science Fundamentals', 'data-science-fundamentals', 'Introduction to data analysis and visualization', '<p>Learn the basics of data science including statistics, data visualization, and introductory machine learning concepts.</p>', 'beginner', 39.99, 'published', 'public', 0, 1, NOW()),
(@tech_academy_id, @tech_instructor_1, @cat_web_dev, 'JavaScript ES6+ Masterclass', 'javascript-es6-masterclass', 'Modern JavaScript for web developers', '<p>Master modern JavaScript including ES6+ features, async programming, and popular frameworks.</p>', 'intermediate', 0.00, 'draft', 'private', 0, 1, NULL);

-- Insert courses for Language School
INSERT INTO courses (tenant_id, instructor_id, category_id, title, slug, short_description, description, level, price, status, visibility, is_featured, certificate_enabled, published_at) VALUES
(@language_school_id, @lang_instructor_1, @cat_spanish, 'Spanish for Beginners', 'spanish-for-beginners', 'Start your Spanish learning journey', '<p>Learn Spanish from scratch with our structured beginner course. Cover basic grammar, vocabulary, and conversational skills.</p>', 'beginner', 0.00, 'published', 'public', 1, 1, NOW()),
(@language_school_id, @lang_instructor_1, @cat_spanish, 'Intermediate Spanish Conversation', 'intermediate-spanish-conversation', 'Improve your Spanish speaking skills', '<p>Practice conversational Spanish with real-life scenarios and native speaker interactions.</p>', 'intermediate', 29.99, 'published', 'public', 0, 1, NOW()),
(@language_school_id, @lang_instructor_1, @cat_french, 'French Basics', 'french-basics', 'Introduction to French language', '<p>Learn French fundamentals including pronunciation, basic grammar, and everyday phrases.</p>', 'beginner', 0.00, 'published', 'public', 1, 1, NOW());

-- Get course IDs
SET @course_web_bootcamp = (SELECT id FROM courses WHERE slug = 'complete-web-development-bootcamp');
SET @course_php_advanced = (SELECT id FROM courses WHERE slug = 'advanced-php-mysql-development');
SET @course_ios = (SELECT id FROM courses WHERE slug = 'ios-app-development-swift');
SET @course_spanish_beginner = (SELECT id FROM courses WHERE slug = 'spanish-for-beginners');
SET @course_spanish_intermediate = (SELECT id FROM courses WHERE slug = 'intermediate-spanish-conversation');

-- Insert course sections for Web Bootcamp
INSERT INTO course_sections (course_id, title, description, display_order) VALUES
(@course_web_bootcamp, 'Getting Started', 'Introduction to web development', 1),
(@course_web_bootcamp, 'HTML Fundamentals', 'Learn HTML5 from basics', 2),
(@course_web_bootcamp, 'CSS Styling', 'Master CSS3 and responsive design', 3),
(@course_web_bootcamp, 'JavaScript Basics', 'Introduction to JavaScript programming', 4),
(@course_web_bootcamp, 'Backend with PHP', 'Server-side programming with PHP', 5);

-- Get section IDs
SET @section_getting_started = (SELECT id FROM course_sections WHERE course_id = @course_web_bootcamp AND display_order = 1);
SET @section_html = (SELECT id FROM course_sections WHERE course_id = @course_web_bootcamp AND display_order = 2);
SET @section_css = (SELECT id FROM course_sections WHERE course_id = @course_web_bootcamp AND display_order = 3);
SET @section_js = (SELECT id FROM course_sections WHERE course_id = @course_web_bootcamp AND display_order = 4);

-- Insert lessons for Web Bootcamp
INSERT INTO lessons (course_id, section_id, title, type, content, video_url, duration_minutes, display_order, is_preview) VALUES
(@course_web_bootcamp, @section_getting_started, 'Welcome to the Course', 'video', NULL, 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 10, 1, 1),
(@course_web_bootcamp, @section_getting_started, 'Setting Up Your Development Environment', 'text', '<h2>Development Environment Setup</h2><p>In this lesson, we will set up all the tools you need:</p><ul><li>Code editor (VS Code)</li><li>Web browser (Chrome)</li><li>Local server (XAMPP)</li></ul>', NULL, 15, 2, 1),
(@course_web_bootcamp, @section_html, 'Introduction to HTML', 'video', NULL, 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 20, 1, 0),
(@course_web_bootcamp, @section_html, 'HTML Tags and Elements', 'video', NULL, 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 25, 2, 0),
(@course_web_bootcamp, @section_html, 'Forms and Input Elements', 'video', NULL, 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 30, 3, 0),
(@course_web_bootcamp, @section_css, 'CSS Basics', 'video', NULL, 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 20, 1, 0),
(@course_web_bootcamp, @section_css, 'Flexbox Layout', 'video', NULL, 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 35, 2, 0),
(@course_web_bootcamp, @section_css, 'CSS Grid', 'video', NULL, 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 35, 3, 0),
(@course_web_bootcamp, @section_js, 'JavaScript Introduction', 'video', NULL, 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 25, 1, 0),
(@course_web_bootcamp, @section_js, 'Variables and Data Types', 'video', NULL, 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 30, 2, 0);

-- Insert sections for Spanish course
INSERT INTO course_sections (course_id, title, description, display_order) VALUES
(@course_spanish_beginner, 'Introduction', 'Welcome to Spanish', 1),
(@course_spanish_beginner, 'Basic Greetings', 'Learn how to greet people', 2),
(@course_spanish_beginner, 'Numbers and Colors', 'Essential vocabulary', 3);

SET @spanish_section_1 = (SELECT id FROM course_sections WHERE course_id = @course_spanish_beginner AND display_order = 1);
SET @spanish_section_2 = (SELECT id FROM course_sections WHERE course_id = @course_spanish_beginner AND display_order = 2);

-- Insert lessons for Spanish course
INSERT INTO lessons (course_id, section_id, title, type, content, video_url, duration_minutes, display_order, is_preview) VALUES
(@course_spanish_beginner, @spanish_section_1, 'Welcome to Spanish Learning', 'video', NULL, 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 5, 1, 1),
(@course_spanish_beginner, @spanish_section_2, 'Basic Greetings', 'video', NULL, 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 15, 1, 0),
(@course_spanish_beginner, @spanish_section_2, 'Practice Greetings', 'text', '<h2>Practice Common Greetings</h2><p>Hola - Hello<br>Buenos días - Good morning<br>Buenas tardes - Good afternoon</p>', NULL, 10, 2, 0);

-- Insert enrollments
INSERT INTO enrollments (tenant_id, student_id, course_id, enrollment_type, status, progress_percent, completed_lessons, total_lessons, last_accessed_at, enrolled_at) VALUES
(@tech_academy_id, @tech_student_1, @course_web_bootcamp, 'free', 'active', 40.00, 4, 10, NOW(), DATE_SUB(NOW(), INTERVAL 7 DAY)),
(@tech_academy_id, @tech_student_2, @course_web_bootcamp, 'free', 'active', 20.00, 2, 10, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY)),
(@tech_academy_id, @tech_student_3, @course_web_bootcamp, 'free', 'completed', 100.00, 10, 10, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 30 DAY)),
(@tech_academy_id, @tech_student_1, @course_php_advanced, 'paid', 'active', 15.00, 1, 8, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),
(@language_school_id, @lang_student_1, @course_spanish_beginner, 'free', 'active', 66.67, 2, 3, NOW(), DATE_SUB(NOW(), INTERVAL 10 DAY)),
(@language_school_id, @lang_student_2, @course_spanish_beginner, 'free', 'completed', 100.00, 3, 3, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 15 DAY));

-- Get enrollment IDs
SET @enrollment_1 = (SELECT id FROM enrollments WHERE student_id = @tech_student_1 AND course_id = @course_web_bootcamp);
SET @enrollment_2 = (SELECT id FROM enrollments WHERE student_id = @tech_student_3 AND course_id = @course_web_bootcamp);
SET @enrollment_3 = (SELECT id FROM enrollments WHERE student_id = @lang_student_2 AND course_id = @course_spanish_beginner);

-- Insert quizzes
INSERT INTO quizzes (tenant_id, course_id, lesson_id, title, description, passing_score, max_attempts, show_correct_answers, is_required) VALUES
(@tech_academy_id, @course_web_bootcamp, NULL, 'HTML Fundamentals Quiz', 'Test your knowledge of HTML basics', 70.00, 3, 1, 1),
(@language_school_id, @course_spanish_beginner, NULL, 'Spanish Greetings Quiz', 'Test your Spanish greeting knowledge', 80.00, 0, 1, 0);

SET @quiz_html = (SELECT id FROM quizzes WHERE title = 'HTML Fundamentals Quiz');
SET @quiz_spanish = (SELECT id FROM quizzes WHERE title = 'Spanish Greetings Quiz');

-- Insert quiz questions
INSERT INTO quiz_questions (quiz_id, question_text, question_type, options, correct_answer, points, display_order) VALUES
(@quiz_html, 'What does HTML stand for?', 'multiple_choice', '["Hyper Text Markup Language", "High Tech Modern Language", "Home Tool Markup Language", "Hyperlinks and Text Markup Language"]', '0', 1.00, 1),
(@quiz_html, 'Which HTML tag is used for the largest heading?', 'multiple_choice', '["<h1>", "<h6>", "<heading>", "<head>"]', '0', 1.00, 2),
(@quiz_html, 'HTML tags are case sensitive', 'true_false', '["True", "False"]', '1', 1.00, 3),
(@quiz_html, 'Which tag is used to create a hyperlink?', 'multiple_choice', '["<a>", "<link>", "<href>", "<url>"]', '0', 1.00, 4),
(@quiz_spanish, '¿Cómo se dice "Good morning" en español?', 'multiple_choice', '["Buenos días", "Buenas tardes", "Buenas noches", "Hola"]', '0', 1.00, 1),
(@quiz_spanish, '¿Cómo se dice "Thank you"?', 'multiple_choice', '["Gracias", "Por favor", "De nada", "Perdón"]', '0', 1.00, 2);

-- Insert quiz attempts
INSERT INTO quiz_attempts (quiz_id, student_id, enrollment_id, score, max_score, percentage, passed, answers, time_spent_seconds, started_at, completed_at) VALUES
(@quiz_html, @tech_student_3, @enrollment_2, 3.00, 4.00, 75.00, 1, '{"1": "0", "2": "0", "3": "1", "4": "1"}', 420, DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),
(@quiz_spanish, @lang_student_2, @enrollment_3, 2.00, 2.00, 100.00, 1, '{"1": "0", "2": "0"}', 180, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Insert certificates
INSERT INTO certificates (tenant_id, enrollment_id, student_id, course_id, certificate_number, issued_at) VALUES
(@tech_academy_id, @enrollment_2, @tech_student_3, @course_web_bootcamp, 'CERT-TA-' || LPAD(@enrollment_2, 8, '0'), DATE_SUB(NOW(), INTERVAL 2 DAY)),
(@language_school_id, @enrollment_3, @lang_student_2, @course_spanish_beginner, 'CERT-LS-' || LPAD(@enrollment_3, 8, '0'), DATE_SUB(NOW(), INTERVAL 1 DAY));

-- Insert invoices
INSERT INTO invoices (tenant_id, subscription_id, invoice_number, amount, currency, status, due_date, paid_at) VALUES
(@tech_academy_id, 1, 'INV-' || DATE_FORMAT(NOW(), '%Y%m') || '-0001', 79.00, 'USD', 'paid', DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),
(@language_school_id, 2, 'INV-' || DATE_FORMAT(NOW(), '%Y%m') || '-0002', 29.00, 'USD', 'paid', DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 4 DAY));

-- Insert payments
INSERT INTO payments (tenant_id, invoice_id, amount, currency, payment_method, transaction_id, status) VALUES
(@tech_academy_id, 1, 79.00, 'USD', 'credit_card', 'TXN-' || MD5(RAND()), 'completed'),
(@language_school_id, 2, 29.00, 'USD', 'credit_card', 'TXN-' || MD5(RAND()), 'completed');

-- Insert sample notifications
INSERT INTO notifications (tenant_id, user_id, type, subject, message, status, sent_at) VALUES
(@tech_academy_id, @tech_student_1, 'enrollment_confirmation', 'Welcome to Complete Web Development Bootcamp', 'You have successfully enrolled in the course. Start learning now!', 'sent', DATE_SUB(NOW(), INTERVAL 7 DAY)),
(@tech_academy_id, @tech_student_3, 'course_completion', 'Congratulations! You completed the course', 'You have successfully completed Complete Web Development Bootcamp. Your certificate is ready!', 'sent', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(@language_school_id, @lang_student_2, 'course_completion', 'Congratulations! You completed the course', 'You have successfully completed Spanish for Beginners. Your certificate is ready!', 'sent', DATE_SUB(NOW(), INTERVAL 1 DAY));

-- Update tenant usage counts
UPDATE tenant_usage SET
    courses_count = (SELECT COUNT(*) FROM courses WHERE tenant_id = @tech_academy_id),
    students_count = (SELECT COUNT(*) FROM users WHERE tenant_id = @tech_academy_id AND role = 'student'),
    enrollments_count = (SELECT COUNT(*) FROM enrollments WHERE tenant_id = @tech_academy_id),
    storage_used_mb = 150
WHERE tenant_id = @tech_academy_id;

UPDATE tenant_usage SET
    courses_count = (SELECT COUNT(*) FROM courses WHERE tenant_id = @language_school_id),
    students_count = (SELECT COUNT(*) FROM users WHERE tenant_id = @language_school_id AND role = 'student'),
    enrollments_count = (SELECT COUNT(*) FROM enrollments WHERE tenant_id = @language_school_id),
    storage_used_mb = 75
WHERE tenant_id = @language_school_id;

-- ========================================
-- END OF SCHEMA AND SEED DATA
-- ========================================
