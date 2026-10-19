<?php

/**
 * Standalone minimal router for testing generated CDD servers.
 *
 * @package Cdd
 */

declare(strict_types=1);

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

$serverDir = getenv('CDD_SERVER_DIR') ?: (file_exists('/tmp/temp-php-server/src/routes.php') ? '/tmp/temp-php-server' : (sys_get_temp_dir() . '/temp-php-server'));

if (file_exists($serverDir . '/src/ApiServers.php')) {
    require_once $serverDir . '/src/ApiServers.php';
}
if (file_exists($serverDir . '/src/ApiController.php')) {
    require $serverDir . '/src/ApiController.php';
}
if (file_exists($serverDir . '/src/routes.php')) {
    require $serverDir . '/src/routes.php';
}

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

$controller = class_exists('ApiController') ? new ApiController() : null;

foreach (MockRoute::$routes as $route) {
    $pattern = preg_replace('/\{[^\}]+\}/', '([^/]+)', $route['uri']);
    $pattern = '#^' . $pattern . '$#';

    if ($route['method'] === $method && preg_match($pattern, $uri, $matches)) {
        array_shift($matches);

        $action = $route['action'];
        if (is_array($action)) {
            $bs = chr(92);
            $class = ltrim((string) $action[0], $bs);
            $methodName = (string) $action[1];
            $file = $serverDir . '/src/Controllers/' . basename(str_replace($bs, '/', $class)) . '.php';
            if (file_exists($file)) {
                require_once $file;
            }
            if (class_exists($class)) {
                $instance = new $class();
                if (method_exists($instance, $methodName)) {
                    ob_start();
                    $ref = new \ReflectionMethod($instance, $methodName);
                    $callArgs = [];
                    foreach ($ref->getParameters() as $idx => $param) {
                        $callArgs[] = $matches[$idx] ?? null;
                    }
                    $result = $instance->$methodName(...$callArgs);
                    $buffered = (string) ob_get_clean();
                    header('Content-Type: application/json');
                    echo $buffered !== '' ? $buffered : json_encode($result ?? ['status' => 'success']);
                    exit;
                }
            }
        } elseif ($controller !== null && is_string($action) && method_exists($controller, $action)) {
            ob_start();
            $ref = new \ReflectionMethod($controller, $action);
            $callArgs = [];
            foreach ($ref->getParameters() as $idx => $param) {
                $callArgs[] = $matches[$idx] ?? null;
            }
            $result = $controller->$action(...$callArgs);
            $buffered = (string) ob_get_clean();
            header('Content-Type: application/json');
            if ($buffered !== '') {
                echo $buffered;
            } elseif ($result !== null) {
                echo json_encode($result);
            } else {
                echo json_encode(['status' => 'success', 'action' => $action]);
            }
            exit;
        }
    }
}

http_response_code(404);
echo 'Not found';
