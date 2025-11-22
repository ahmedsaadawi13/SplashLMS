<?php
// FILE: /app/core/View.php

/**
 * View class - Helper for rendering views
 */
class View
{
    /**
     * Render a view with layout
     *
     * @param string $view View file path
     * @param array $data Data to pass to view
     * @param string $layout Layout file to use
     */
    public static function render($view, $data = [], $layout = 'layouts/main')
    {
        extract($data);

        // Start output buffering for content
        ob_start();
        $viewFile = __DIR__ . '/../views/' . $view . '.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            die("View not found: {$viewFile}");
        }
        $content = ob_get_clean();

        // Render layout with content
        $layoutFile = __DIR__ . '/../views/' . $layout . '.php';
        if (file_exists($layoutFile)) {
            require $layoutFile;
        } else {
            echo $content;
        }
    }

    /**
     * Escape output for HTML
     *
     * @param string $value
     * @return string
     */
    public static function escape($value)
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Generate URL
     *
     * @param string $path
     * @return string
     */
    public static function url($path = '')
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
    public static function asset($path)
    {
        return self::url('assets/' . ltrim($path, '/'));
    }
}
