<?php
// FILE: /app/core/Controller.php

/**
 * Base Controller class
 * All controllers should extend this class
 */
class Controller
{
    /**
     * Render a view file
     *
     * @param string $view View file path (without .php extension)
     * @param array $data Data to pass to the view
     */
    protected function view($view, $data = [])
    {
        // Extract data array to variables
        extract($data);

        // Build view file path
        $viewFile = __DIR__ . '/../views/' . $view . '.php';

        if (!file_exists($viewFile)) {
            die("View not found: {$viewFile}");
        }

        require_once $viewFile;
    }

    /**
     * Load a model
     *
     * @param string $model Model name
     * @return object Model instance
     */
    protected function model($model)
    {
        $modelFile = __DIR__ . '/../models/' . $model . '.php';

        if (!file_exists($modelFile)) {
            die("Model not found: {$modelFile}");
        }

        require_once $modelFile;

        if (!class_exists($model)) {
            die("Model class not found: {$model}");
        }

        return new $model();
    }

    /**
     * Redirect to another URL
     *
     * @param string $path
     */
    protected function redirect($path)
    {
        header("Location: " . $this->url($path));
        exit;
    }

    /**
     * Generate URL with base path
     *
     * @param string $path
     * @return string
     */
    protected function url($path = '')
    {
        $baseUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/');
        $path = ltrim($path, '/');
        return $baseUrl . '/' . $path;
    }

    /**
     * Return JSON response
     *
     * @param mixed $data
     * @param int $statusCode
     */
    protected function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Check if user is authenticated
     *
     * @return bool
     */
    protected function isAuthenticated()
    {
        return isset($_SESSION['user_id']);
    }

    /**
     * Require authentication - redirect to login if not authenticated
     */
    protected function requireAuth()
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('login');
        }
    }

    /**
     * Get current authenticated user
     *
     * @return array|null
     */
    protected function getUser()
    {
        if (!$this->isAuthenticated()) {
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
     * Check if user has a specific role
     *
     * @param string|array $roles
     * @return bool
     */
    protected function hasRole($roles)
    {
        $user = $this->getUser();
        if (!$user) {
            return false;
        }

        if (is_array($roles)) {
            return in_array($user['role'], $roles);
        }

        return $user['role'] === $roles;
    }

    /**
     * Require specific role - redirect if unauthorized
     *
     * @param string|array $roles
     */
    protected function requireRole($roles)
    {
        $this->requireAuth();

        if (!$this->hasRole($roles)) {
            http_response_code(403);
            die('Access denied. Insufficient permissions.');
        }
    }

    /**
     * Validate CSRF token
     *
     * @return bool
     */
    protected function validateCSRF()
    {
        $token = $_POST['csrf_token'] ?? '';
        return $token === ($_SESSION['csrf_token'] ?? '');
    }

    /**
     * Generate CSRF token
     *
     * @return string
     */
    protected function generateCSRF()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Get input from POST or GET
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function input($key, $default = null)
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    /**
     * Sanitize string input
     *
     * @param string $input
     * @return string
     */
    protected function sanitize($input)
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Set flash message
     *
     * @param string $key
     * @param string $message
     */
    protected function flash($key, $message)
    {
        $_SESSION['flash'][$key] = $message;
    }

    /**
     * Get and clear flash message
     *
     * @param string $key
     * @return string|null
     */
    protected function getFlash($key)
    {
        $message = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $message;
    }
}
