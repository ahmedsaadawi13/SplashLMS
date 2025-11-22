<?php
// FILE: /config/routes.php

/**
 * Application routes configuration
 * All routes are defined here
 */

// Public routes
$router->get('', 'HomeController@index');
$router->get('about', 'HomeController@about');
$router->get('catalog', 'CatalogController@index');
$router->get('catalog/{slug}', 'CatalogController@show');
$router->get('course/{slug}', 'CatalogController@course');

// Authentication routes
$router->get('login', 'AuthController@loginForm');
$router->post('login', 'AuthController@login');
$router->get('register', 'AuthController@registerForm');
$router->post('register', 'AuthController@register');
$router->get('logout', 'AuthController@logout');
$router->get('forgot-password', 'AuthController@forgotPasswordForm');
$router->post('forgot-password', 'AuthController@forgotPassword');

// Dashboard routes
$router->get('dashboard', 'DashboardController@index');

// Student routes
$router->get('my-courses', 'StudentController@myCourses');
$router->get('learn/{slug}', 'StudentController@learn');
$router->post('enroll/{id}', 'StudentController@enroll');
$router->post('lesson/complete/{id}', 'StudentController@completeLesson');
$router->get('quiz/{id}', 'StudentController@quiz');
$router->post('quiz/{id}/submit', 'StudentController@submitQuiz');
$router->get('certificate/{id}', 'StudentController@certificate');

// Instructor/Admin - Course Management
$router->get('admin/courses', 'CourseController@index');
$router->get('admin/courses/create', 'CourseController@create');
$router->post('admin/courses/store', 'CourseController@store');
$router->get('admin/courses/{id}/edit', 'CourseController@edit');
$router->post('admin/courses/{id}/update', 'CourseController@update');
$router->post('admin/courses/{id}/delete', 'CourseController@delete');

// Course sections
$router->post('admin/courses/{id}/sections/store', 'SectionController@store');
$router->post('admin/sections/{id}/update', 'SectionController@update');
$router->post('admin/sections/{id}/delete', 'SectionController@delete');

// Course lessons
$router->get('admin/courses/{courseId}/lessons/create', 'LessonController@create');
$router->post('admin/courses/{courseId}/lessons/store', 'LessonController@store');
$router->get('admin/lessons/{id}/edit', 'LessonController@edit');
$router->post('admin/lessons/{id}/update', 'LessonController@update');
$router->post('admin/lessons/{id}/delete', 'LessonController@delete');

// Quiz management
$router->get('admin/courses/{courseId}/quizzes/create', 'QuizController@create');
$router->post('admin/courses/{courseId}/quizzes/store', 'QuizController@store');
$router->get('admin/quizzes/{id}/edit', 'QuizController@edit');
$router->post('admin/quizzes/{id}/update', 'QuizController@update');
$router->post('admin/quizzes/{id}/delete', 'QuizController@delete');

// Quiz questions
$router->post('admin/quizzes/{id}/questions/store', 'QuizController@storeQuestion');
$router->post('admin/questions/{id}/update', 'QuizController@updateQuestion');
$router->post('admin/questions/{id}/delete', 'QuizController@deleteQuestion');

// Student management
$router->get('admin/students', 'AdminController@students');
$router->get('admin/students/create', 'AdminController@createStudent');
$router->post('admin/students/store', 'AdminController@storeStudent');
$router->get('admin/students/{id}/edit', 'AdminController@editStudent');
$router->post('admin/students/{id}/update', 'AdminController@updateStudent');

// Enrollment management
$router->get('admin/enrollments', 'EnrollmentController@index');
$router->post('admin/enrollments/store', 'EnrollmentController@store');
$router->post('admin/enrollments/{id}/delete', 'EnrollmentController@delete');

// Analytics
$router->get('admin/analytics', 'AnalyticsController@index');
$router->get('admin/analytics/course/{id}', 'AnalyticsController@course');

// Categories
$router->get('admin/categories', 'CategoryController@index');
$router->post('admin/categories/store', 'CategoryController@store');
$router->post('admin/categories/{id}/update', 'CategoryController@update');
$router->post('admin/categories/{id}/delete', 'CategoryController@delete');

// Tenant Admin - Settings
$router->get('admin/settings', 'SettingsController@index');
$router->post('admin/settings/update', 'SettingsController@update');

// Tenant Admin - Subscription & Billing
$router->get('admin/subscription', 'SubscriptionController@index');
$router->get('admin/invoices', 'SubscriptionController@invoices');
$router->get('admin/invoices/{id}', 'SubscriptionController@viewInvoice');
$router->post('admin/subscription/upgrade', 'SubscriptionController@upgrade');
$router->post('admin/payment/simulate', 'PaymentController@simulate');

// Platform Admin routes
$router->get('platform/dashboard', 'PlatformController@dashboard');
$router->get('platform/tenants', 'PlatformController@tenants');
$router->get('platform/tenants/create', 'PlatformController@createTenant');
$router->post('platform/tenants/store', 'PlatformController@storeTenant');
$router->get('platform/tenants/{id}/edit', 'PlatformController@editTenant');
$router->post('platform/tenants/{id}/update', 'PlatformController@updateTenant');
$router->get('platform/plans', 'PlatformController@plans');

// API routes
$router->get('api/catalog', 'ApiController@catalog');
$router->post('api/enroll', 'ApiController@enroll');
$router->get('api/course/{id}', 'ApiController@courseDetail');

// File upload
$router->post('upload', 'UploadController@upload');
