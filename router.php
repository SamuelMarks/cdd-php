<?php

/**
 * Standalone minimal router for testing generated CDD servers.
 *
 * @package Cdd
 */

declare(strict_types=1);

// A standalone minimal router for testing the generated CDD server
require 'vendor/autoload.php';

/**
 * Mock Laravel Route facade for standalone server routing.
 */
class MockRoute
{
    /**
     * @var array<int, array<string, mixed>> Registered routes.
     */
    public static array $routes = [];

    /**
     * Registers a route definition dynamically.
     *
     * @param string $method HTTP method name.
     * @param array<int, mixed> $args Route URI and action arguments.
     * @return void
     */
    public static function __callStatic(string $method, array $args): void
    {
        self::$routes[] = ['method' => strtoupper($method), 'uri' => $args[0], 'action' => $args[1]];
    }
}
class_alias('MockRoute', 'Illuminate\Support\Facades\Route');

require '/tmp/temp-php-server/src/ApiController.php';
require '/tmp/temp-php-server/src/routes.php';

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$controller = new ApiController();

foreach (MockRoute::$routes as $route) {
    // Very simple path matching
    $pattern = preg_replace('/\{[^\}]+\}/', '([^/]+)', $route['uri']);
    $pattern = '#^' . $pattern . '$#';

    if ($route['method'] === $method && preg_match($pattern, $uri, $matches)) {
        array_shift($matches); // remove full match

        $action = $route['action'];
        if (method_exists($controller, $action)) {
            // For testing, just return a dummy response
            header('Content-Type: application/json');
            echo json_encode(["status" => "success", "action" => $action]);
            exit;
        }
    }
}

http_response_code(404);
echo "Not found";
