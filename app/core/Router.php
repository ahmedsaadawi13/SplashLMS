<?php
// FILE: /app/core/Router.php

/**
 * Router class - Handles URL routing and request dispatching
 */
class Router
{
    protected $routes = [];
    protected $notFoundCallback;

    /**
     * Add a GET route
     *
     * @param string $path
     * @param string $controller Controller@method format
     */
    public function get($path, $controller)
    {
        $this->addRoute('GET', $path, $controller);
    }

    /**
     * Add a POST route
     *
     * @param string $path
     * @param string $controller Controller@method format
     */
    public function post($path, $controller)
    {
        $this->addRoute('POST', $path, $controller);
    }

    /**
     * Add a route to the routing table
     *
     * @param string $method HTTP method
     * @param string $path URL path
     * @param string $controller Controller@method format
     */
    protected function addRoute($method, $path, $controller)
    {
        // Convert route path to regex pattern
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';

        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'controller' => $controller,
            'path' => $path
        ];
    }

    /**
     * Set 404 not found callback
     *
     * @param callable $callback
     */
    public function notFound($callback)
    {
        $this->notFoundCallback = $callback;
    }

    /**
     * Dispatch the request to the appropriate controller
     */
    public function dispatch()
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = $_SERVER['REQUEST_URI'];

        // Remove query string and trim slashes
        $requestUri = strtok($requestUri, '?');
        $requestUri = trim($requestUri, '/');

        foreach ($this->routes as $route) {
            if ($route['method'] === $requestMethod && preg_match($route['pattern'], $requestUri, $matches)) {
                // Extract controller and method
                list($controllerName, $method) = explode('@', $route['controller']);

                // Load controller file
                $controllerFile = __DIR__ . '/../controllers/' . $controllerName . '.php';
                if (!file_exists($controllerFile)) {
                    die("Controller file not found: {$controllerFile}");
                }

                require_once $controllerFile;

                if (!class_exists($controllerName)) {
                    die("Controller class not found: {$controllerName}");
                }

                $controller = new $controllerName();

                if (!method_exists($controller, $method)) {
                    die("Method {$method} not found in controller {$controllerName}");
                }

                // Extract route parameters (excluding numeric keys)
                $params = array_filter($matches, function($key) {
                    return !is_numeric($key);
                }, ARRAY_FILTER_USE_KEY);

                // Call controller method with parameters
                call_user_func_array([$controller, $method], $params);
                return;
            }
        }

        // No route matched - 404
        if ($this->notFoundCallback) {
            call_user_func($this->notFoundCallback);
        } else {
            http_response_code(404);
            echo "404 - Page Not Found";
        }
    }
}
