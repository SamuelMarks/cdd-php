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

$serverDir = getenv('CDD_SERVER_DIR') ?: (is_dir(__DIR__ . '/src/Routes') ? __DIR__ : (sys_get_temp_dir() . '/temp-php-server'));
if (file_exists($serverDir . '/src/routes.php')) {
    require $serverDir . '/src/routes.php';
} elseif (file_exists('/tmp/temp-php-server/src/routes.php')) {
    require '/tmp/temp-php-server/src/routes.php';
}

$request = Request::capture();
try {
    $response = $router->dispatch($request);
    $response->send();
} catch (\Exception $e) {
    echo $e->getMessage();
}
