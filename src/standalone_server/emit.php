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
 *
 * @package Api
 */

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';
if (!class_exists({$bs}Api{$bs}Database{$bs}DatabaseConnection::class)) {
    spl_autoload_register(static function (string \$class): void {
        if (str_starts_with(\$class, 'Api{$bs}{$bs}')) {
            \$rel = str_replace('{$bs}{$bs}', '/', substr(\$class, 4));
            \$file = __DIR__ . '/src/' . \$rel . '.php';
            if (is_file(\$file)) {
                require_once \$file;
            }
        }
    });
}

\$_dbUrl = (string) getenv('DATABASE_URL');
if (class_exists({$bs}Api{$bs}Database{$bs}DatabaseConnection::class)) {
    \$_useEphemeral = \$_dbUrl === '' || \$_dbUrl === 'sqlite::memory:' || getenv('EPHEMERAL_DB') === 'true';
    try {
        {$bs}Api{$bs}Database{$bs}DatabaseConnection::connect(\$_useEphemeral);
        {$bs}Api{$bs}Database{$bs}DatabaseConnection::migrate();
        if (\$_useEphemeral && class_exists({$bs}Api{$bs}Seeder{$bs}DatabaseSeeder::class)) {
            (new {$bs}Api{$bs}Seeder{$bs}DatabaseSeeder())->seed();
        }
        if (\$_useEphemeral) {
            putenv('DATABASE_URL=sqlite::memory:');
        }
    } catch ({$bs}Throwable \$_dbErr) {
        if (!\$_useEphemeral) {
            try {
                {$bs}Api{$bs}Database{$bs}DatabaseConnection::connect(true);
                {$bs}Api{$bs}Database{$bs}DatabaseConnection::migrate();
                if (class_exists({$bs}Api{$bs}Seeder{$bs}DatabaseSeeder::class)) {
                    (new {$bs}Api{$bs}Seeder{$bs}DatabaseSeeder())->seed();
                }
                putenv('DATABASE_URL=sqlite::memory:');
            } catch ({$bs}Throwable \$_fallbackErr) {
                // Proceed without seeded DB if migration fails
            }
        }
    }
}

/**
 * Mock Route collector for standalone server dispatching.
 */
class MockRoute {
    /** @var array<int, array<string, mixed>> Registered routes. */
    public static array \$routes = [];

    /**
     * Registers a route definition dynamically.
     *
     * @param string \$method HTTP method.
     * @param array<int, mixed> \$args Route arguments.
     * @return void
     */
    public static function __callStatic(string \$method, array \$args): void {
        self::\$routes[] = ['method' => strtoupper(\$method), 'uri' => \$args[0], 'action' => \$args[1]];
    }
}
class_alias('MockRoute', 'Illuminate{$bs}Support{$bs}Facades{$bs}Route');

if (file_exists(__DIR__ . '/src/ServerRunner.php')) {
    require_once __DIR__ . '/src/ServerRunner.php';
}
if (file_exists(__DIR__ . '/src/ApiServers.php')) {
    require_once __DIR__ . '/src/ApiServers.php';
}
if (file_exists(__DIR__ . '/src/ApiController.php')) {
    require __DIR__ . '/src/ApiController.php';
}
require __DIR__ . '/src/routes.php';

\$method = \$_SERVER['REQUEST_METHOD'] ?? 'GET';
\$uri = (string) parse_url(\$_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

// Global CORS Middleware (permissive default for local UI development)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin, X-Api-Key, X-Mock-Scopes');
header('Access-Control-Max-Age: 86400');

if (strtoupper(\$method) === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Administrative Webhook Trigger Endpoint
if (strtoupper(\$method) === 'POST' && preg_match('#^/_mock/trigger-webhook/([^/]+)\$#', \$uri, \$whMatches)) {
    \$webhookName = \$whMatches[1];
    \$rawBody = json_decode((string) file_get_contents('php://input'), true);
    \$body = is_array(\$rawBody) ? \$rawBody : [];
    \$targetUrl = (string) (\$body['target_url'] ?? '');
    \$payload = is_array(\$body['payload'] ?? null) ? \$body['payload'] : \$body;
    if (class_exists('Api{$bs}WebhookDispatcher')) {
        \$dispatcher = new {$bs}Api{$bs}WebhookDispatcher();
        \$res = \$dispatcher->trigger(\$webhookName, \$targetUrl, \$payload);
        http_response_code((int) (\$res['status'] ?? 200));
        header('Content-Type: application/json');
        echo json_encode(\$res);
        exit;
    }
}

// Integrated Identity Provider (IdP) Endpoints (--start-auth-server)
if (getenv('CDD_START_AUTH_SERVER') === 'true' && str_starts_with(\$uri, '/auth/') && class_exists('Api{$bs}IdentityProvider')) {
    \$idp = new {$bs}Api{$bs}IdentityProvider();
    \$rawBody = json_decode((string) file_get_contents('php://input'), true);
    \$body = is_array(\$rawBody) ? \$rawBody : [];
    \$authRes = match (\$uri) {
        '/auth/register' => \$idp->register(\$body),
        '/auth/login' => \$idp->login(\$body),
        '/auth/refresh' => \$idp->refresh(\$body),
        '/auth/logout' => \$idp->logout((string) (\$_SERVER['HTTP_AUTHORIZATION'] ?? '')),
        default => ['status' => 404, 'data' => ['error' => 'Not found']],
    };
    http_response_code((int) \$authRes['status']);
    header('Content-Type: application/json');
    echo json_encode(\$authRes['data']);
    exit;
}

\$prefixes = [];
\$serversClass = class_exists('ApiServers', false) ? 'ApiServers' : (class_exists({$bs}Api{$bs}ApiServers::class, false) ? {$bs}Api{$bs}ApiServers::class : '');
if (\$serversClass !== '') {
    \$serversObj = new \$serversClass();
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

usort(MockRoute::\$routes, static function (array \$a, array \$b): int {
    return substr_count((string) \$a['uri'], '{') <=> substr_count((string) \$b['uri'], '{');
});

foreach (MockRoute::\$routes as \$route) {
    \$pattern = preg_replace('/\{[^\}]+\}/', '([^/]+)', \$route['uri']);
    \$pattern = '#^' . \$pattern . '\$#';

    if (\$route['method'] === \$method && preg_match(\$pattern, \$uri, \$matches)) {
        array_shift(\$matches);
        \$action = \$route['action'];

        if (getenv('CDD_ENFORCE_AUTH') === 'true' && class_exists('Api{$bs}AuthMiddleware')) {
            \$reqHeaders = [
                'Authorization' => (string) (\$_SERVER['HTTP_AUTHORIZATION'] ?? ''),
                'X-Api-Key' => (string) (\$_SERVER['HTTP_X_API_KEY'] ?? ''),
                'X-Mock-Scopes' => (string) (\$_SERVER['HTTP_X_MOCK_SCOPES'] ?? ''),
            ];
            \$authRes = {$bs}Api{$bs}AuthMiddleware::authenticate(
                \$reqHeaders,
                \$_GET,
                [['BearerAuth' => []]],
                ['BearerAuth' => ['type' => 'http', 'scheme' => 'bearer']],
                true,
                getenv('EPHEMERAL_DB') === 'true' || !getenv('DATABASE_URL')
            );
            if (!\$authRes['authenticated']) {
                http_response_code((int) \$authRes['status']);
                header('Content-Type: application/json');
                echo json_encode(['error' => \$authRes['error'] ?? 'Unauthorized']);
                exit;
            }
        }

        if (getenv('CDD_STRICT_VALIDATION') === 'true' && in_array(strtoupper(\$method), ['POST', 'PUT', 'PATCH'], true) && class_exists('Api{$bs}ValidationMiddleware')) {
            \$rawInput = json_decode((string) file_get_contents('php://input'), true);
            \$payload = is_array(\$rawInput) ? \$rawInput : [];
            \$schema = is_array(\$route['schema'] ?? null) ? \$route['schema'] : [];
            \$valRes = {$bs}Api{$bs}ValidationMiddleware::validateRequest(\$payload, \$schema, true);
            if (!\$valRes['valid']) {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(\$valRes);
                exit;
            }
        }

        \$buildCallArgs = static function ({$bs}ReflectionMethod \$ref, array \$matches): array {
            \$callArgs = [];
            \$rawBody = json_decode((string) file_get_contents('php://input'), true);
            \$parsedBody = is_array(\$rawBody) ? \$rawBody : [];
            \$matchIdx = 0;
            foreach (\$ref->getParameters() as \$param) {
                \$pName = \$param->getName();
                \$pType = \$param->getType();
                \$tName = \$pType instanceof {$bs}ReflectionNamedType ? \$pType->getName() : 'mixed';
                if (\$pName === 'body') {
                    if (\$tName === 'array') {
                        \$callArgs[] = \$parsedBody;
                    } elseif (\$tName === 'object') {
                        \$callArgs[] = (object) \$parsedBody;
                    } else {
                        if (!class_exists(\$tName)) {
                            \$shortModel = substr(\$tName, (int) strrpos(\$tName, '{$bs}{$bs}') + 1);
                            \$modelClass = 'Api{$bs}Models{$bs}{$bs}' . \$shortModel;
                            if (class_exists(\$modelClass)) {
                                class_alias(\$modelClass, \$tName);
                            }
                        }
                        \$callArgs[] = class_exists(\$tName) ? new \$tName(\$parsedBody) : (\$param->allowsNull() ? null : (object) \$parsedBody);
                    }
                    continue;
                }
                \$rawVal = \$matches[\$matchIdx] ?? (\$_GET[\$pName] ?? null);
                if (isset(\$matches[\$matchIdx])) {
                    \$matchIdx++;
                }
                if (\$rawVal === null) {
                    if (\$param->allowsNull()) {
                        \$callArgs[] = null;
                    } elseif (\$tName === 'int') {
                        \$callArgs[] = 1;
                    } elseif (\$tName === 'float') {
                        \$callArgs[] = 1.0;
                    } elseif (\$tName === 'bool') {
                        \$callArgs[] = true;
                    } elseif (\$tName === 'array') {
                        \$callArgs[] = [];
                    } else {
                        \$callArgs[] = '';
                    }
                } else {
                    if (\$tName === 'int') {
                        \$callArgs[] = (int) \$rawVal;
                    } elseif (\$tName === 'float') {
                        \$callArgs[] = (float) \$rawVal;
                    } elseif (\$tName === 'bool') {
                        \$callArgs[] = filter_var(\$rawVal, FILTER_VALIDATE_BOOLEAN);
                    } elseif (\$tName === 'array') {
                        \$callArgs[] = is_array(\$rawVal) ? \$rawVal : [(string) \$rawVal];
                    } else {
                        \$callArgs[] = (string) \$rawVal;
                    }
                }
            }
            return \$callArgs;
        };

        \$executed = false;
        \$output = '';
        \$statusCode = 200;
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
                        \$callArgs = \$buildCallArgs(\$ref, \$matches);
                        \$res = \$instance->\$methodName(...\$callArgs);
                        \$output = (string) ob_get_clean();
                        if (\$output === '' && \$res !== null) {
                            \$output = (string) json_encode(\$res);
                        }
                    } catch ({$bs}Throwable \$e) {
                        ob_end_clean();
                        \$statusCode = is_a(\$e, 'Api{$bs}Exceptions{$bs}MockServerError') ? 501 : 500;
                        \$output = (string) json_encode(['error' => \$e->getMessage()]);
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
                        \$callArgs = \$buildCallArgs(\$ref, \$matches);
                        \$res = \$controller->\$actionName(...\$callArgs);
                        \$output = (string) ob_get_clean();
                        if (\$output === '' && \$res !== null) {
                            \$output = (string) json_encode(\$res);
                        }
                    } catch ({$bs}Throwable \$e) {
                        ob_end_clean();
                        \$statusCode = is_a(\$e, 'Api{$bs}Exceptions{$bs}MockServerError') ? 501 : 500;
                        \$output = (string) json_encode(['error' => \$e->getMessage()]);
                    }
                    \$executed = true;
                }
            }
        }

        if (\$executed) {
            if (\$statusCode !== 200) {
                http_response_code(\$statusCode);
            }
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
