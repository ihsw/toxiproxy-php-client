<?php

namespace Ihsw\Toxiproxy\Test;

use React\EventLoop\Factory as EventLoopFactory;
use React\Socket\Connector as SocketConnector;
use React\Socket\ConnectionInterface;
use Ihsw\Toxiproxy\Proxy;

trait AssertionHelpers
{
    /**
     * @param Proxy $proxy
     * @param null $message
     */
    protected function assertProxyAvailable(Proxy $proxy, $message = null)
    {
        $this->assertConnection(
            ["ip" => $proxy->getListenIp(), "port" => $proxy->getListenPort()],
            $message
        );
    }

    /**
     * @param Proxy $proxy
     * @param null $message
     */
    protected function assertProxyUnavailable(Proxy $proxy, $message = null)
    {
        $this->assertConnection(
            ["ip" => $proxy->getListenIp(), "port" => $proxy->getListenPort(), "match" => false],
            $message
        );
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
            "match" => true
        ], $options);

        // client setup
        $clientLoop = EventLoopFactory::create();
        $connector = new SocketConnector($clientLoop);
        $info = sprintf("%s:%s", $settings["ip"], $settings["port"]);
        $promise = $connector->connect($info)
            ->then(function (ConnectionInterface $conn) use ($clientLoop) {
                $conn->close();
                return true;
            }, function () {
                return false;
            });
        $clientLoop->run();

        // catching the output
        $out = null;
        $promise->done(function ($v) use (&$out) {
            $out = $v;
        });

        $this->assertEquals($out, $settings["match"], $message);
    }
}
