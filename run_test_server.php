<?php

/**
 * Illuminate Router test server dispatcher.
 *
 * @package Cdd
 */

declare(strict_types=1);

require 'vendor/autoload.php';

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Facade;

$container = new Container();
$events = new Dispatcher($container);
$router = new Router($events, $container);

Facade::setFacadeApplication($container);
$container->instance('router', $router);

$serverDir = (string) ($_ENV['CDD_SERVER_DIR'] ?? getenv('CDD_SERVER_DIR') ?: __DIR__);
if (file_exists($serverDir . '/src/routes.php')) {
    require $serverDir . '/src/routes.php';
}

$request = Request::capture();
try {
    $response = $router->dispatch($request);
    $response->send();
} catch (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}
