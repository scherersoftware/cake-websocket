<?php
declare(strict_types = 1);
namespace Websocket\Shell;

use Cake\Console\ConsoleOptionParser;
use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
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
     * @return \Cake\Console\ConsoleOptionParser
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
        $websocketInterface = new WebsocketInterface();

        $logger = null;
        if (isset($this->params['logger']) && Log::engine($this->params['logger']) !== false) {
            $logger = Log::engine($this->params['logger']);
        }

        $websocketWorker = new WebsocketWorker($loop, $websocketInterface, Queue::engine('default'), $logger);

        $websocketWorker->attachListener('Worker.job.exception', function ($event): void {
            $exception = $event->data['exception'];
            $exception->job = $event->data['job'];
        });

        $websocketWorker->attachListener('Worker.job.start', function ($event): void {
            ConnectionManager::get('default')->disconnect();
        });

        $websocketWorker->attachListener('Worker.job.success', function ($event): void {
            ConnectionManager::get('default')->disconnect();
        });

        $websocketWorker->attachListener('Worker.job.failure', function ($event): void {
            $failedJob = $event->data['job'];
            $failedItem = $failedJob->item();
            $options = [
                'queue' => 'failed',
                'failedJob' => $failedJob,
            ];
            Queue::push($failedItem['class'], $failedJob->data(), $options);
            ConnectionManager::get('default')->disconnect();
        });

        $serverSocket = new Server('0.0.0.0' . ':' . Configure::read('Websocket.port'), $loop);
        $webServer = new IoServer(
            new HttpServer(
                new WsServer(
                    new WampServer(
                        $websocketInterface
                    )
                )
            ),
            $serverSocket,
            $loop
        );

        $loop->run();
    }
}
