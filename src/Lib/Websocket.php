<?php
namespace Websocket\Lib;

use Cake\Core\Configure;
use Cake\Utility\Hash;
use Josegonzalez\CakeQueuesadilla\Queue\Queue;

/**
 * Use this class to enqueue Websocket events
 */
class Websocket
{
    /**
     * Publish event by adding queue entry to database
     *
     * @param  string $eventName name of event
     * @param  array $payload    additional data which is passed as is to websocket clients
     *                           Avoid sending sensitive data here!
     * @param array $audience    manipulate the configured audience
     *                           options:  [
     *                                      // whether all not authenticated clients should receive the event (overwrites event default)
     *                                      'includeAllNotAuthenticated' => false,
     *                                      // whether all authenticated clients should receive the event (overwrites event default)
     *                                      'includeAllAuthenticated' => true,
     *                                      // authenticated clients to send the event to (works independent of the settings above)
     *                                      'userIds' => []
     *                                     ]
     * @return bool
     * @throws \Exception if config of given event name is invalid
     */
    public static function publishEvent(string $eventName, array $payload = [], array $audience = []): bool
    {
        if (PHP_SAPI == 'cli') {
            return false;
        }

        if (!self::validateEventConfig($eventName)) {
            throw new \Exception('Invalid Websocket event config.');
        }

        $audience = Hash::merge(Configure::read('Websocket.events.' . $eventName . '.audience'), $audience);

        return Queue::push($eventName, [
            'payload' => $payload,
            'audience' => $audience
        ], [
            'queue' => Configure::read('Websocket.Queue.name')
        ]);
    }

    /**
     * Validates config of one given or all configured events
     *
     * @param  string|null $eventName name of event
     * @return bool
     */
    public static function validateEventConfig(string $eventName = null): bool
    {
        $eventConfigs = Configure::read('Websocket.events');

        if (!is_null($eventName) && !array_key_exists($eventName, $eventConfigs)) {
            return false;
        }

        foreach ($eventConfigs as $eventName => $eventConfig) {
            if (empty($eventConfig['audience'])) {
                return false;
            }

            if (!isset($eventConfig['audience']['includeAllNotAuthenticated'])
                || !is_bool($eventConfig['audience']['includeAllNotAuthenticated'])
            ) {
                return false;
            }

            if (!isset($eventConfig['audience']['includeAllAuthenticated'])
                || !is_bool($eventConfig['audience']['includeAllAuthenticated'])
            ) {
                return false;
            }

            if (isset($eventConfig['audience']['userIds'])) {
                if (!is_array($eventConfig['audience']['userIds'])) {
                    return false;
                }
            } else {
                Configure::write('Websocket.events.' . $eventName . '.audience.userIds', []);
            }
        }

        return true;
    }

    /**
     * get reduced websocket config for the frontend
     *
     * @return array
     */
    public static function getFrontendConfig(): array
    {
        $host = Configure::read('Websocket.ssl') ? 'wss://' : 'ws://';
        $host .= Configure::read('Websocket.host');

        return [
            'host' => $host,
            'port' => Configure::read('Websocket.port')
        ];
    }
}
