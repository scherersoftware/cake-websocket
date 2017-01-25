<?php
namespace Websocket\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\TestSuite\TestCase;
use Websocket\Controller\Component\WebsocketComponent;

/**
 * Websocket\Controller\Component\WebsocketComponent Test Case
 */
class WebsocketComponentTest extends TestCase
{

    /**
     * Test subject
     *
     * @var \Websocket\Controller\Component\WebsocketComponent
     */
    public $Websocket;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $registry = new ComponentRegistry();
        $this->Websocket = new WebsocketComponent($registry);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Websocket);

        parent::tearDown();
    }

    /**
     * Test initial setup
     *
     * @return void
     */
    public function testInitialization()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
