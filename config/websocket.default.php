<?php
return [
    'Websocket' => [
        'ssl' => false,
        'host' => 'cws.dev',
        'externalHost' => 'cws.dev',
        'port' => 8889,
        'frontendPath' => [
            'ssl' => [
                'path' => '/wss/',
                'usePort' => false
            ],
            'normal' => [
                'path' => '/',
                'usePort' => true
            ]
        ],
        'sessionCookieName' => 'cws',
        'Queue' => [
            'name' => 'websocket',
            'loopInterval' => 0.1,
        ]
    ]
];
