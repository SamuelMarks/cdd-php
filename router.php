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

if (file_exists('/tmp/temp-php-server/src/ApiServers.php')) {
    require_once '/tmp/temp-php-server/src/ApiServers.php';
}
require '/tmp/temp-php-server/src/ApiController.php';
require '/tmp/temp-php-server/src/routes.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if (class_exists('ApiServers')) {
    $serversObj = new ApiServers();
    foreach (get_object_vars($serversObj) as $propVal) {
        if (is_string($propVal)) {
            $parsedPath = parse_url($propVal, PHP_URL_PATH);
            if (is_string($parsedPath) && $parsedPath !== '' && $parsedPath !== '/' && str_starts_with($uri, rtrim($parsedPath, '/'))) {
                $uri = substr($uri, strlen(rtrim($parsedPath, '/')));
                if ($uri === '') {
                    $uri = '/';
                }
                break;
            }
        }
    }
}

$controller = new ApiController();

foreach (MockRoute::$routes as $route) {
    // Very simple path matching
    $pattern = preg_replace('/\{[^\}]+\}/', '([^/]+)', $route['uri']);
    $pattern = '#^' . $pattern . '$#';

    if ($route['method'] === $method && preg_match($pattern, $uri, $matches)) {
        array_shift($matches); // remove full match

        $action = $route['action'];
        if (is_string($action) && method_exists($controller, $action)) {
            ob_start();
            $ref = new \ReflectionMethod($controller, $action);
            $callArgs = [];
            foreach ($ref->getParameters() as $idx => $param) {
                if (isset($matches[$idx])) {
                    $callArgs[] = $matches[$idx];
                } else {
                    $callArgs[] = null;
                }
            }
            $result = $controller->$action(...$callArgs);
            $buffered = (string) ob_get_clean();
            header('Content-Type: application/json');
            if ($buffered !== '') {
                echo $buffered;
            } elseif ($result !== null) {
                echo json_encode($result);
            } else {
                echo json_encode(["status" => "success", "action" => $action]);
            }
            exit;
        }
    }
}

http_response_code(404);
echo "Not found";
