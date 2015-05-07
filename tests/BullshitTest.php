<?php

use React\EventLoop\Factory as EventLoopFactory,
    React\Dns\Resolver\Factory as DnsResolverFactory,
    React\Socket\Server as SocketServer,
    React\SocketClient\Connector as SocketConnector,
    React\SocketClient\ConnectionException as SocketConnectionException,
    React\Stream\Stream as SocketStream;

class BullshitTest extends \PHPUnit_Framework_TestCase
{
    public function testBullshit()
    {
        // misc
        $ip = "127.0.0.1";
        $port = 43434;

        // server setup
        $serverLoop = EventLoopFactory::create();
        $server = new SocketServer($serverLoop);
        $server->listen($port);

        // client setup
        $clientLoop = EventLoopFactory::create();
        $dnsResolverFactory = new DnsResolverFactory();
        $dns = $dnsResolverFactory->createCached("8.8.8.8", $clientLoop); // dunno why dns is required for this shit
        $connector = new SocketConnector($clientLoop, $dns);
        $promise = $connector->create($ip, $port)->then(function (SocketStream $stream) {
            $stream->close();
            return true;
        }, function(SocketConnectionException $e) {
            return false;
        });
        $clientLoop->run();

        // catching the output
        $out = null;
        $promise->done(function($v) use(&$out) {
            $out = $v;
        });

        // cleanup
        $server->shutdown();

        $this->assertTrue($out, sprintf("Could not verify connection to %s", $port));
    }
}