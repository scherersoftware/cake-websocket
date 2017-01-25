<?php
namespace Websocket\Lib;

use Cake\Core\Configure;
use Cake\Log\Log;
use Exception;
use Josegonzalez\CakeQueuesadilla\Queue\Queue;
use josegonzalez\Queuesadilla\Engine\EngineInterface;
use josegonzalez\Queuesadilla\Worker\Base;
use josegonzalez\Queuesadilla\Worker\Listener\StatsListener;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;

/**
 * WebsocketWorker
 *
 */
class WebsocketWorker extends Base
{

    /**
     * passed instance of react loop interface
     * @var LoopInterface
     */
    private $__loop;

    /**
     * passed instance of websocket interface
     * @var LoopInterface
     */
    private $__websocketInterface;

    /**
     * constructor
     *
     * @param LoopInterface      $loop               instance of react loop interface
     * @param WebsocketInterface $websocketInterface instance of websocket interface
     * @param EngineInterface    $engine             queue engine (MySQL hard coded)
     * @param LoggerInterface    $logger             logger to use (error logger hard coded)
     * @param array              $params             additional queue params (ignored)
     * @return void
     */
    public function __construct(LoopInterface $loop, WebsocketInterface $websocketInterface, EngineInterface $engine, LoggerInterface $logger = null)
    {
        $this->__loop = $loop;
        $this->__websocketInterface = $websocketInterface;

        $this->__loop->addPeriodicTimer(Configure::read('Websocket.Queue.loopInterval'), function() {
            $this->work();
        });

        $this->__initialSetup();
    }

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function work()
    {
        if (!$this->connect()) {
            $this->logger()->alert(sprintf('Worker unable to connect, exiting'));
            $this->dispatchEvent('Worker.job.connectionFailed');
            return false;
        }

        $jobClass = $this->engine->getJobClass();

        $this->iterations++;
        $item = $this->engine->pop($this->queue);
        $this->dispatchEvent('Worker.job.seen', ['item' => $item]);
        if (empty($item)) {
            $this->logger()->debug('No job!');
            $this->dispatchEvent('Worker.job.empty');
            return;
        }

        $success = false;
        $job = new $jobClass($item, $this->engine);

        try {
            $success = $this->perform($item);
        } catch (Exception $e) {
            $this->logger()->alert(sprintf('Exception: "%s"', $e->getMessage()));
            $this->dispatchEvent('Worker.job.exception', [
                'job' => $job,
                'exception' => $e,
            ]);
        }

        if ($success) {
            $this->logger()->debug('Success. Acknowledging job on queue.');
            $job->acknowledge();
            $this->dispatchEvent('Worker.job.success', ['job' => $job]);
            return;
        }

        $this->logger()->info('Failed. Releasing job to queue');
        $job->release();
        $this->dispatchEvent('Worker.job.failure', ['job' => $job]);

        return true;
    }

    /**
     * Connect to the data storage using the configured engine
     *
     * @return bool
     */
    public function connect(): bool
    {
        $maxIterations = $this->maxIterations ? sprintf(', max iterations %s', $this->maxIterations) : '';
        $this->logger()->info(sprintf('Starting worker%s', $maxIterations));
        return (bool)$this->engine->connection();
    }

    /**
     * Publishes event based on database entry
     *
     * @param  array   $queueEntry database entry
     * @return bool
     */
    public function perform(array $queueEntry): bool
    {
        return $this->__websocketInterface->publishEvent($queueEntry);
    }

    /**
     * helper method to do all the dirty construction work
     *
     * @return void
     */
    private function __initialSetup(): void
    {
        // FIXME clean this up maybe
        $logger = Log::engine('error');
        $engine = Queue::engine('default');
        $engine->setLogger($logger);
        $engine->config('queue', Configure::read('Websocket.Queue.name'));
        $this->engine = $engine;
        $this->queue = Configure::read('Websocket.Queue.name');
        $this->maxIterations = null;
        $this->iterations = 0;
        $this->maxRuntime = null;
        $this->runtime = 0;
        $this->name = get_class($this->engine) . ' Worker';
        $this->setLogger($logger);
        $this->StatsListener = new StatsListener;
        $this->attachListener($this->StatsListener);
        register_shutdown_function(array(&$this, 'shutdownHandler'));
    }

    protected function disconnect()
    {
    }
}
