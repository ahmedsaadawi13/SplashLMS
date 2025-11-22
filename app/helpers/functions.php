<?php
// FILE: /app/helpers/functions.php

/**
 * Global helper functions
 */

/**
 * Load environment variables from .env file
 *
 * @param string $path Path to .env file
 */
function loadEnv($path)
{
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse key=value
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes if present
            if (preg_match('/^(["\'])(.*)\\1$/', $value, $matches)) {
                $value = $matches[2];
            }

            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
}

/**
 * Get configuration value
 *
 * @param string $key Dot notation key (e.g., 'app.name')
 * @param mixed $default Default value
 * @return mixed
 */
function config($key, $default = null)
{
    static $config = [];

    if (empty($config)) {
        $config = [
            'app' => require __DIR__ . '/../../config/app.php',
        ];
    }

    $keys = explode('.', $key);
    $value = $config;

    foreach ($keys as $k) {
        if (!isset($value[$k])) {
            return $default;
        }
        $value = $value[$k];
    }

    return $value;
}

/**
 * Generate URL
 *
 * @param string $path
 * @return string
 */
function url($path = '')
{
    $baseUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/');
    $path = ltrim($path, '/');
    return $baseUrl . '/' . $path;
}

/**
 * Generate asset URL
 *
 * @param string $path
 * @return string
 */
function asset($path)
{
    return url('assets/' . ltrim($path, '/'));
}

/**
 * Redirect to URL
 *
 * @param string $path
 */
function redirect($path)
{
    header("Location: " . url($path));
    exit;
}

/**
 * Escape HTML output
 *
 * @param string $value
 * @return string
 */
function e($value)
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Check if user is authenticated
 *
 * @return bool
 */
function isAuth()
{
    return isset($_SESSION['user_id']);
}

/**
 * Get authenticated user data
 *
 * @return array|null
 */
function auth()
{
    if (!isAuth()) {
        return null;
    }

    return [
        'id' => $_SESSION['user_id'] ?? null,
        'tenant_id' => $_SESSION['tenant_id'] ?? null,
        'role' => $_SESSION['role'] ?? null,
        'name' => $_SESSION['name'] ?? null,
        'email' => $_SESSION['email'] ?? null,
    ];
}

/**
 * Check if user has role
 *
 * @param string|array $roles
 * @return bool
 */
function hasRole($roles)
{
    $user = auth();
    if (!$user) {
        return false;
    }

    if (is_array($roles)) {
        return in_array($user['role'], $roles);
    }

    return $user['role'] === $roles;
}

/**
 * Format date
 *
 * @param string $date
 * @param string $format
 * @return string
 */
function formatDate($date, $format = 'M d, Y')
{
    if (!$date) {
        return '';
    }
    return date($format, strtotime($date));
}

/**
 * Format currency
 *
 * @param float $amount
 * @param string $currency
 * @return string
 */
function formatCurrency($amount, $currency = 'USD')
{
    $symbols = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
    ];

    $symbol = $symbols[$currency] ?? $currency . ' ';
    return $symbol . number_format($amount, 2);
}

/**
 * Get flash message and clear it
 *
 * @param string $key
 * @return string|null
 */
function flash($key)
{
    $message = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $message;
}

/**
 * Set flash message
 *
 * @param string $key
 * @param string $message
 */
function setFlash($key, $message)
{
    $_SESSION['flash'][$key] = $message;
}

/**
 * Generate random string
 *
 * @param int $length
 * @return string
 */
function randomString($length = 32)
{
    return bin2hex(random_bytes($length / 2));
}

/**
 * Sanitize input
 *
 * @param string $input
 * @return string
 */
function sanitize($input)
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email
 *
 * @param string $email
 * @return bool
 */
function isValidEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Get file extension
 *
 * @param string $filename
 * @return string
 */
function getFileExtension($filename)
{
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Format bytes to human readable
 *
 * @param int $bytes
 * @param int $precision
 * @return string
 */
function formatBytes($bytes, $precision = 2)
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }

    return round($bytes, $precision) . ' ' . $units[$i];
}

/**
 * Calculate pagination
 *
 * @param int $total Total items
 * @param int $page Current page
 * @param int $perPage Items per page
 * @return array
 */
function paginate($total, $page = 1, $perPage = 20)
{
    $totalPages = ceil($total / $perPage);
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;

    return [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $page,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'has_prev' => $page > 1,
        'has_next' => $page < $totalPages,
    ];
}

/**
 * Truncate text
 *
 * @param string $text
 * @param int $length
 * @param string $suffix
 * @return string
 */
function truncate($text, $length = 100, $suffix = '...')
{
    if (strlen($text) <= $length) {
        return $text;
    }

    return substr($text, 0, $length) . $suffix;
}

/**
 * Debug dump and die
 *
 * @param mixed $data
 */
function dd($data)
{
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    die();
}

/**
 * Get request method
 *
 * @return string
 */
function requestMethod()
{
    return $_SERVER['REQUEST_METHOD'];
}

/**
 * Check if request is POST
 *
 * @return bool
 */
function isPost()
{
    return requestMethod() === 'POST';
}

/**
 * Check if request is GET
 *
 * @return bool
 */
function isGet()
{
    return requestMethod() === 'GET';
}

/**
 * Get current URL path
 *
 * @return string
 */
function currentPath()
{
    return trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
}

/**
 * Check if current path matches
 *
 * @param string $path
 * @return bool
 */
function isCurrentPath($path)
{
    return currentPath() === trim($path, '/');
}
