<?php
return [
    'Websocket' => [
        'ssl' => false,
        'host' => 'cws.dev',
        'port' => 8889,
        'sessionCookieName' => 'cws',
        'Queue' => [
            'name' => 'websocket',
            'loopInterval' => .1,
        ]
    ]
];
