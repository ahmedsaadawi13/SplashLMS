<?php
// FILE: /config/app.php

/**
 * Application configuration
 */
return [
    'name' => $_ENV['APP_NAME'] ?? 'SplashLMS',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'url' => $_ENV['APP_URL'] ?? 'http://localhost',
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'UTC',

    'session' => [
        'lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 7200),
        'cookie_name' => 'splashlms_session',
        'cookie_path' => '/',
        'cookie_domain' => '',
        'cookie_secure' => false,
        'cookie_httponly' => true,
    ],

    'upload' => [
        'max_size' => (int)($_ENV['UPLOAD_MAX_SIZE'] ?? 10485760), // 10MB default
        'allowed_types' => explode(',', $_ENV['UPLOAD_ALLOWED_TYPES'] ?? 'jpg,jpeg,png,gif,pdf'),
        'path' => __DIR__ . '/../storage/uploads',
    ],

    'pagination' => [
        'items_per_page' => (int)($_ENV['ITEMS_PER_PAGE'] ?? 20),
    ],

    'security' => [
        'password_min_length' => (int)($_ENV['PASSWORD_MIN_LENGTH'] ?? 8),
        'csrf_token_name' => $_ENV['CSRF_TOKEN_NAME'] ?? 'csrf_token',
    ],
];
