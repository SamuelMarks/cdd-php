<?php

declare(strict_types=1);

namespace Cdd\StandaloneServer;

/**
 * emit
 */
function emit(): string
{
    return <<<'PHP'
<?php
/**
 * Standalone development server for testing.
 * Run with: php -S 127.0.0.1:8080 server.php
 */

require __DIR__ . '/vendor/autoload.php';

class MockRoute {
    public static $routes = [];
    public static function __callStatic($method, $args) {
        self::$routes[] = ['method' => strtoupper($method), 'uri' => $args[0], 'action' => $args[1]];
    }
}
class_alias('MockRoute', 'Illuminate\Support\Facades\Route');

if (file_exists(__DIR__ . '/src/ApiController.php')) {
    require __DIR__ . '/src/ApiController.php';
}
require __DIR__ . '/src/routes.php';

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = preg_replace('#^/api/v3#', '', $uri);
$uri = preg_replace('#^/v2#', '', $uri);

foreach (MockRoute::$routes as $route) {
    $pattern = preg_replace('/\{[^\}]+\}/', '([^/]+)', $route['uri']);
    $pattern = '#^' . $pattern . '$#';

    if ($route['method'] === $method && preg_match($pattern, $uri, $matches)) {
        array_shift($matches);
        $action = $route['action'];

        $executed = false;
        if (is_array($action)) {
            $class = ltrim($action[0], '\\');
            $methodName = $action[1];
            $file = __DIR__ . '/src/Controllers/' . basename(str_replace('\\', '/', $class)) . '.php';
            if (file_exists($file)) require_once $file;
            if (class_exists($class)) {
                $instance = new $class();
                if (method_exists($instance, $methodName)) {
                    $executed = true;
                }
            }
        } else {
            if (class_exists('ApiController')) {
                $controller = new ApiController();
                if (method_exists($controller, $action)) {
                    $executed = true;
                }
            }
        }

        if ($executed) {
            header('Content-Type: application/json');
            echo json_encode(["status" => "success"]);
            exit;
        }
    }
}

http_response_code(404);
echo json_encode(["error" => "Not found: $method $uri"]);
PHP;
}
