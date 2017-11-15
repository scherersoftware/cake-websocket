<?php
namespace Websocket\Lib;

use App\Lib\Environment;
use Cake\Core\Configure;
use Cake\Network\Session;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\WampServerInterface;

class WebsocketInterface implements WampServerInterface
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
     * Publish an event base on given queue entry
     *
     * @param  array $queueEntry database entry array
     * @return true
     */
    public function publishEvent(array $queueEntry): bool
    {
        $eventName = $queueEntry['class'];
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
     * @param  ConnectionInterface $connection websocket client connection
     * @return null|string
     */
    private function __getUserIdFromSession(ConnectionInterface $connection): ?string
    {
        $userId = null;
        $sessionId = null;
        $sessionCookieName = Configure::read('Websocket.sessionCookieName');
        if (!empty($connection->WebSocket->request->getCookies()[$sessionCookieName])) {
            $sessionId = $connection->WebSocket->request->getCookies()[$sessionCookieName];
        }
        if (!empty($sessionId)) {
            session_abort();
            $this->__session->id($sessionId);
            session_start();
            $userId = $this->__session->read('Auth.User.id');
        }

        return $userId;
    }
}
