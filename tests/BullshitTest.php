<?php

use React\EventLoop\StreamSelectLoop,
    React\Socket\Server,
    React\SocketClient\Connector;

class BullshitTest extends \PHPUnit_Framework_TestCase
{
    private function createResolverMock()
    {
        return $this->getMockBuilder("React\Dns\Resolver\Resolver")
                    ->disableOriginalConstructor()
                    ->getMock();
    }

    /** @test */
    public function connectionToTcpServerShouldSucceed()
    {
        // starting up a server
        $loop = new StreamSelectLoop();
        $server = new Server($loop);
        $server->on("connection", function () use ($server, $loop) {
            $server->shutdown();
        });
        $server->listen(9999);

        // hooking the client up to the server
        $capturedStream = null;
        $connector = new Connector($loop, $this->createResolverMock());
        $connector->create("127.0.0.1", 9999)->then(function ($stream) use (&$capturedStream) {
            $capturedStream = $stream;
            $stream->end();
        });
        $loop->run();

        $this->assertInstanceOf("React\Stream\Stream", $capturedStream);
    }
}