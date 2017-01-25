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
     * @return bool
     * @throws \Exception if config of given event name is invalid
     */
    public static function publishEvent(string $eventName, array $payload = [], array $audience = []): bool
    {
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
}
