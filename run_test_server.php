<?php

require 'vendor/autoload.php';

use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;

$container = new Container();
$events = new Dispatcher($container);
$router = new Router($events, $container);

Facade::setFacadeApplication($container);
$container->instance('router', $router);

if (file_exists('/tmp/temp-php-server/src/routes.php')) {
    require '/tmp/temp-php-server/src/routes.php';
}

$request = Request::capture();
try {
    $response = $router->dispatch($request);
    $response->send();
} catch (\Exception $e) {
    echo $e->getMessage();
}
