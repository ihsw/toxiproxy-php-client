<?php

use Ihsw\Toxiproxy\Test\AbstractTest;
use React\EventLoop\Factory as EventLoopFactory,
    React\Dns\Resolver\Factory as DnsResolverFactory,
    React\Socket\Server as SocketServer,
    React\SocketClient\Connector as SocketConnector,
    React\SocketClient\ConnectionException as SocketConnectionException,
    React\Stream\Stream as SocketStream;

class BullshitTest extends AbstractTest
{
    public function testStartAndConnect()
    {
        $options = [
            "ip" => "127.0.0.1",
            "port" => 44444,
            "startServer" => true
        ];
        $this->assertCanConnect($options, "Could not verify starting 127.0.0.1:44444 and being available");
    }

    public function testCanConnect()
    {
        $options = [
            "ip" => "127.0.0.1",
            "port" => 44445,
            "startServer" => false
        ];
        $this->assertCanConnect($options, "Could not verify 127.0.0.1:44445 being available");
    }

    public function testCannotConnect()
    {
        $options = [
            "ip" => "127.0.0.1",
            "port" => 44446,
            "startServer" => false,
            "match" => false
        ];
        $this->assertCanConnect($options, "Could not verify 127.0.0.1:44446 being unavailable");
    }
}