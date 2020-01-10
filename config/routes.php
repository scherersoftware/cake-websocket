<?php

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;

Router::plugin(
    'Websocket',
    ['path' => '/websocket'],
    function (RouteBuilder $routes): void {
        $routes->fallbacks(DashedRoute::class);
    }
);
