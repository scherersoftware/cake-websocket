<?php

use Cake\Core\Configure;
use Cake\Utility\Hash;
use Websocket\Lib\Websocket;

$config = include 'websocket.default.php';
$config = $config['Websocket'];
$appWebsocketConfig = Configure::read('Websocket');
if ($appWebsocketConfig) {
    $config = Hash::merge($config, $appWebsocketConfig);
}
Configure::write('Websocket', $config);

$websocketEventsConfigFilePath = CONFIG . 'websocket_events.php';

if (!empty($config['Websocket.events'])) {
    throw new \Exception('Please configure your Websocket events in: ' . $websocketEventsConfigFilePath);
}

if (!file_exists($websocketEventsConfigFilePath)) {
    throw new \Exception('Please create a Websocket event config file: ' . $websocketEventsConfigFilePath);
}

$eventConfig = require $websocketEventsConfigFilePath;

Configure::write('Websocket.events', $eventConfig);

if (!Websocket::validateEventConfig()) {
    throw new \Exception('Invalid Websocket event config.');
}
