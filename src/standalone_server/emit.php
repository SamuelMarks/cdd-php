<?php

/**
 * Emits a standalone PHP development server script (`server.php`).
 *
 * @package Cdd\StandaloneServer
 */

declare(strict_types=1);

namespace Cdd\StandaloneServer;

/**
 * Emits a standalone development server script for PHP's built-in web server.
 *
 * @return string The generated standalone server PHP code.
 */
function emit(): string
{
    $bs = chr(92);
    return <<<PHP
<?php
/**
 * Standalone development server for testing.
 * Run with: php -S 127.0.0.1:8080 server.php
 */

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

class MockRoute {
    public static array \$routes = [];
    public static function __callStatic(string \$method, array \$args): void {
        self::\$routes[] = ['method' => strtoupper(\$method), 'uri' => \$args[0], 'action' => \$args[1]];
    }
}
class_alias('MockRoute', 'Illuminate{$bs}Support{$bs}Facades{$bs}Route');

if (file_exists(__DIR__ . '/src/ApiServers.php')) {
    require_once __DIR__ . '/src/ApiServers.php';
}
if (file_exists(__DIR__ . '/src/ApiController.php')) {
    require __DIR__ . '/src/ApiController.php';
}
require __DIR__ . '/src/routes.php';

\$method = \$_SERVER['REQUEST_METHOD'] ?? 'GET';
\$uri = (string) parse_url(\$_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

\$prefixes = [];
if (class_exists('ApiServers')) {
    \$serversObj = new ApiServers();
    foreach (get_object_vars(\$serversObj) as \$propVal) {
        if (is_string(\$propVal)) {
            \$parsedPath = parse_url(\$propVal, PHP_URL_PATH);
            if (is_string(\$parsedPath) && \$parsedPath !== '' && \$parsedPath !== '/') {
                \$prefixes[] = rtrim(\$parsedPath, '/');
            }
        }
    }
}
foreach (array_unique(\$prefixes) as \$prefix) {
    if (\$prefix !== '' && str_starts_with(\$uri, \$prefix)) {
        \$uri = substr(\$uri, strlen(\$prefix));
        if (\$uri === '') {
            \$uri = '/';
        }
        break;
    }
}

foreach (MockRoute::\$routes as \$route) {
    \$pattern = preg_replace('/\{[^\}]+\}/', '([^/]+)', \$route['uri']);
    \$pattern = '#^' . \$pattern . '\$#';

    if (\$route['method'] === \$method && preg_match(\$pattern, \$uri, \$matches)) {
        array_shift(\$matches);
        \$action = \$route['action'];

        \$executed = false;
        \$output = '';
        if (is_array(\$action)) {
            \$class = ltrim((string) \$action[0], '{$bs}{$bs}');
            \$methodName = (string) \$action[1];
            \$file = __DIR__ . '/src/Controllers/' . basename(str_replace('{$bs}{$bs}', '/', \$class)) . '.php';
            if (file_exists(\$file)) {
                require_once \$file;
            }
            if (class_exists(\$class)) {
                \$instance = new \$class();
                if (method_exists(\$instance, \$methodName)) {
                    ob_start();
                    try {
                        \$ref = new {$bs}ReflectionMethod(\$instance, \$methodName);
                        \$callArgs = [];
                        foreach (\$ref->getParameters() as \$idx => \$param) {
                            \$callArgs[] = \$matches[\$idx] ?? null;
                        }
                        \$res = \$instance->\$methodName(...\$callArgs);
                        \$output = (string) ob_get_clean();
                        if (\$output === '' && \$res !== null) {
                            \$output = (string) json_encode(\$res);
                        }
                    } catch ({$bs}Throwable \$e) {
                        ob_end_clean();
                    }
                    \$executed = true;
                }
            }
        } else {
            if (class_exists('ApiController')) {
                \$controller = new ApiController();
                \$actionName = (string) \$action;
                if (method_exists(\$controller, \$actionName)) {
                    ob_start();
                    try {
                        \$ref = new {$bs}ReflectionMethod(\$controller, \$actionName);
                        \$callArgs = [];
                        foreach (\$ref->getParameters() as \$idx => \$param) {
                            \$callArgs[] = \$matches[\$idx] ?? null;
                        }
                        \$res = \$controller->\$actionName(...\$callArgs);
                        \$output = (string) ob_get_clean();
                        if (\$output === '' && \$res !== null) {
                            \$output = (string) json_encode(\$res);
                        }
                    } catch ({$bs}Throwable \$e) {
                        ob_end_clean();
                    }
                    \$executed = true;
                }
            }
        }

        if (\$executed) {
            header('Content-Type: application/json');
            echo \$output !== '' ? \$output : json_encode(["status" => "success"]);
            exit;
        }
    }
}

http_response_code(404);
echo json_encode(["error" => "Not found: \$method \$uri"]);
PHP;
}
