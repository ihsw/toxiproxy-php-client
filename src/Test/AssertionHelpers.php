<?php

namespace Ihsw\Toxiproxy\Test;

use React\EventLoop\Factory as EventLoopFactory;
use React\Dns\Resolver\Factory as DnsResolverFactory;
use React\Socket\Server as SocketServer;
use React\SocketClient\Connector as SocketConnector;
use React\SocketClient\ConnectionException as SocketConnectionException;
use React\Stream\Stream as SocketStream;

trait AssertionHelpers
{
    /**
     * @param Proxy $proxy
     * @param null $message
     */
    protected function assertProxyAvailable(Proxy $proxy, $message = null)
    {
        list($ip, $port) = explode(":", $proxy->getListen());
        $this->assertConnection(["ip" => $ip, "port" => $port], $message);
    }

    /**
     * @param Proxy $proxy
     * @param null $message
     */
    protected function assertProxyUnavailable(Proxy $proxy, $message = null)
    {
        list($ip, $port) = explode(":", $proxy->getListen());
        $this->assertConnection(["ip" => $ip, "port" => $port, "match" => false], $message);
    }

    /**
     * @param array $options
     * @param null $message
     */
    public function assertConnection(array $options, $message = null)
    {
        $settings = array_merge([
            "ip" => "0.0.0.0",
            "port" => 0,
            "startServer" => false,
            "match" => true
        ], $options);

        // optionally starting server
        if ($settings["startServer"]) {
            $serverLoop = EventLoopFactory::create();
            $server = new SocketServer($serverLoop);
            $server->listen($settings["port"]);
        }

        // client setup
        $clientLoop = EventLoopFactory::create();
        $dnsResolverFactory = new DnsResolverFactory();
        $dns = $dnsResolverFactory->createCached("8.8.8.8", $clientLoop); // dunno why dns is required for this shit
        $connector = new SocketConnector($clientLoop, $dns);
        $promise = $connector->create($settings["ip"], $settings["port"])->then(function (SocketStream $stream) {
            $stream->close();
            return true;
        }, function (SocketConnectionException $e) {
            return false;
        });
        $clientLoop->run();

        // catching the output
        $out = null;
        $promise->done(function ($v) use (&$out) {
            $out = $v;
        });

        // optionally cleaning up the server
        if ($settings["startServer"]) {
            $server->shutdown();
        }

        $this->assertEquals($out, $settings["match"], $message);
    }
}