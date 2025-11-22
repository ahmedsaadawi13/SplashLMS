<?php
// FILE: /public/index.php

/**
 * SplashLMS - Multi-Tenant Learning Management System
 * Entry Point
 */

// Start session
session_start();

// Set default timezone
date_default_timezone_set('UTC');

// Load environment variables
require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');

// Load core classes
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/Router.php';
require_once __DIR__ . '/../app/core/View.php';

// Create router
$router = new Router();

// Load routes
require_once __DIR__ . '/../config/routes.php';

// Set 404 handler
$router->notFound(function() {
    http_response_code(404);
    echo "<h1>404 - Page Not Found</h1>";
});

// Dispatch request
$router->dispatch();
