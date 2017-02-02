<?php
return [
    'Websocket' => [
        'ssl' => false,
        'host' => 'cws.dev',
        'port' => 8889,
        'frontendPath' => [
            'normal' => '/',
            'ssl' => '/'
        ],
        'sessionCookieName' => 'cws',
        'Queue' => [
            'name' => 'websocket',
            'loopInterval' => 0.1,
        ]
    ]
];
