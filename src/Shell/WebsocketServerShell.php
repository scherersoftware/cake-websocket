<?php
namespace Websocket\Shell;

use Cake\Console\ConsoleOptionParser;
use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\Log\Log;
use Josegonzalez\CakeQueuesadilla\Queue\Queue;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\Wamp\WampServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;
use React\Socket\Server;
use Websocket\Lib\WebsocketInterface;
use Websocket\Lib\WebsocketWorker;

/**
 * Websocket Server Shell
 *
 */
class WebsocketServerShell extends Shell
{

    /**
     * Gets the option parser instance and configures it.
     *
     * @return ConsoleOptionParser
     */
    public function getOptionParser(): ConsoleOptionParser
    {
        $parser = parent::getOptionParser();
        $parser->addOption('logger', [
            'help' => 'Name of a configured logger',
            'short' => 'l',
        ]);

        return $parser;
    }

    /**
     * main function
     *
     * @return void
     */
    public function main(): void
    {
        $loop = Factory::create();
        $websocketInterface = new WebsocketInterface;

        $logger = null;
        if (isset($this->params['logger']) && Log::engine($this->params['logger']) !== false) {
            $logger = Log::engine($this->params['logger']);
        }

        $websocketWorker = new WebsocketWorker($loop, $websocketInterface, Queue::engine('default'), $logger);

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
