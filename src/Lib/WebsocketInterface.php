<?php
declare(strict_types = 1);
namespace Websocket\Lib;

use Cake\Core\Configure;
use Cake\Http\Session;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\WampServerInterface;
use Ratchet\WebSocket\WsServerInterface;

class WebsocketInterface implements WampServerInterface, WsServerInterface
{

    /**
     * All currently active connections with connection and user id
     *
     * @var array
     */
    private $__connections = [];

    /**
     * Cake Session instance
     *
     * @var \Cake\Network\Session
     */
    private $__session = null;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        $this->__session = Session::create(Configure::read('Session'));
    }

    /**
     * @return string[]
     */
    public function getSubProtocols(): array
    {
        return ['ocpp1.6'];
    }

    /**
     * Dummy function so valid callable for queue job can be given
     *
     * @return bool
     */
    public static function mockEventReceiver(): bool
    {
        return true;
    }

    /**
     * Publish an event base on given queue entry
     *
     * @param  array $queueEntry database entry array
     * @return true
     */
    public function publishEvent(array $queueEntry): bool
    {
        $eventName = $queueEntry['args'][0]['eventName'];
        $payload = $queueEntry['args'][0]['payload'];
        $audience = $queueEntry['args'][0]['audience'];

        foreach ($this->__connections as $connection) {
            if ($this->_userIsInAudience($connection['userId'], $audience)) {
                $connection['connection']->send(json_encode(compact('eventName', 'payload')));
            }
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function onOpen(ConnectionInterface $connection): void
    {
        $symphonySessionId = $connection->wrappedConn->WAMP->sessionId;
        $this->__connections[$symphonySessionId]['connection'] = $connection;
        $this->__connections[$symphonySessionId]['userId'] = $this->__getUserIdFromSession($connection);
    }

    /**
     * {@inheritDoc}
     */
    public function onClose(ConnectionInterface $connection): void
    {
        unset($this->__connections[$connection->wrappedConn->WAMP->sessionId]);
    }

    /**
     * {@inheritDoc}
     */
    public function onError(ConnectionInterface $connection, \Exception $e): void
    {
        unset($this->__connections[$connection->wrappedConn->WAMP->sessionId]);
    }

    /**
     * {@inheritDoc}
     */
    public function onCall(ConnectionInterface $connection, $id, $topic, array $params): void
    {
        $connection->close();
    }

    /**
     * {@inheritDoc}
     */
    public function onPublish(ConnectionInterface $connection, $topic, $event, array $exclude, array $eligible): void
    {
        $connection->close();
    }

    /**
     * {@inheritDoc}
     */
    public function onUnSubscribe(ConnectionInterface $connection, $topic): void
    {
        $connection->close();
    }

    /**
     * {@inheritDoc}
     */
    public function onSubscribe(ConnectionInterface $connection, $topic): void
    {
        $connection->close();
    }

    /**
     * Check if user is in given target audience
     *
     * @param  null|string $userId   user identifier or null if user is not logged in
     * @param  array       $audience ruleset of audience for event
     * @return bool
     */
    protected function _userIsInAudience(?string $userId, array $audience): bool
    {
        if (in_array($userId, $audience['userIds'])) {
            return true;
        }
        if (empty($userId)) {
            return $audience['includeAllNotAuthenticated'];
        } else {
            return $audience['includeAllAuthenticated'];
        }
    }

    /**
     * Get the user id from the session
     *
     * @param \Ratchet\ConnectionInterface $connection websocket client connection
     * @return null|string
     */
    private function __getUserIdFromSession(ConnectionInterface $connection): ?string
    {
        $userId = null;
        $sessionId = null;
        $sessionCookieName = Configure::read('Websocket.sessionCookieName');

        $cookieHeader = $connection->wrappedConn->httpRequest->getHeaderLine('Cookie');
        $cookies = $this->parseCookie($cookieHeader)['cookies'];

        if (!empty($cookies[$sessionCookieName])) {
            $sessionId = $cookies[$sessionCookieName];
        }
        if (!empty($sessionId)) {
            session_abort();
            $this->__session->id($sessionId);
            session_start();
            $userId = (string)$this->__session->read('Auth.User.id');
        }

        return $userId;
    }

    private static $cookieParts = array(
        'domain'      => 'Domain',
        'path'        => 'Path',
        'max_age'     => 'Max-Age',
        'expires'     => 'Expires',
        'version'     => 'Version',
        'secure'      => 'Secure',
        'port'        => 'Port',
        'discard'     => 'Discard',
        'comment'     => 'Comment',
        'comment_url' => 'Comment-Url',
        'http_only'   => 'HttpOnly'
    );

    /**
     * Taken from Guzzle3
     */
    private function parseCookie($cookie, $host = null, $path = null, $decode = false) {
        // Explode the cookie string using a series of semicolons
        $pieces = array_filter(array_map('trim', explode(';', $cookie)));

        // The name of the cookie (first kvp) must include an equal sign.
        if (empty($pieces) || !strpos($pieces[0], '=')) {
            return false;
        }

        // Create the default return array
        $data = array_merge(array_fill_keys(array_keys(self::$cookieParts), null), array(
            'cookies'   => array(),
            'data'      => array(),
            'path'      => $path ?: '/',
            'http_only' => false,
            'discard'   => false,
            'domain'    => $host
        ));
        $foundNonCookies = 0;

        // Add the cookie pieces into the parsed data array
        foreach ($pieces as $part) {

            $cookieParts = explode('=', $part, 2);
            $key = trim($cookieParts[0]);

            if (count($cookieParts) == 1) {
                // Can be a single value (e.g. secure, httpOnly)
                $value = true;
            } else {
                // Be sure to strip wrapping quotes
                $value = trim($cookieParts[1], " \n\r\t\0\x0B\"");
                if ($decode) {
                    $value = urldecode($value);
                }
            }

            // Only check for non-cookies when cookies have been found
            if (!empty($data['cookies'])) {
                foreach (self::$cookieParts as $mapValue => $search) {
                    if (!strcasecmp($search, $key)) {
                        $data[$mapValue] = $mapValue == 'port' ? array_map('trim', explode(',', $value)) : $value;
                        $foundNonCookies++;
                        continue 2;
                    }
                }
            }

            // If cookies have not yet been retrieved, or this value was not found in the pieces array, treat it as a
            // cookie. IF non-cookies have been parsed, then this isn't a cookie, it's cookie data. Cookies then data.
            $data[$foundNonCookies ? 'data' : 'cookies'][$key] = $value;
        }

        // Calculate the expires date
        if (!$data['expires'] && $data['max_age']) {
            $data['expires'] = time() + (int) $data['max_age'];
        }

        return $data;
    }
}
