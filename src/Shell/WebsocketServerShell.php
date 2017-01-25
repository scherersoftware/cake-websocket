<?php
namespace Websocket\Shell;

use Cake\Console\Shell;
use Cake\Core\Configure;
use Josegonzalez\CakeQueuesadilla\Queue\Queue;
use React\EventLoop\Factory;
use React\Socket\Server;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\Wamp\WampServer;
use Websocket\Lib\WebsocketInterface;
use Websocket\Lib\WebsocketWorker;

/**
 * Websocket Server Shell
 *
 */
class WebsocketServerShell extends Shell
{

    /**
     * main function
     *
     * @return void
     */
    public function main(): void
    {
        $loop = Factory::create();
        $websocketInterface = new WebsocketInterface;

        $websocketWorker = new WebsocketWorker($loop, $websocketInterface, Queue::engine('default'));

        $serverSocket = new Server($loop);
        $serverSocket->listen(Configure::read('Websocket.port'), Configure::read('Websocket.host'));

        $webServer = new IoServer(
            new HttpServer(
                new WsServer(
                    new WampServer(
                        $websocketInterface
                    )
                )
            ),
            $serverSocket
        );

        $loop->run();
    }
}
