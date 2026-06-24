<?php

// A standalone minimal router for testing the generated CDD server
require 'vendor/autoload.php';

// Mock Laravel's Route facade
class MockRoute
{
    public static $routes = [];
    public static function __callStatic($method, $args)
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
