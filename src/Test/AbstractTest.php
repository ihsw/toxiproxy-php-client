<?php namespace Ihsw\Toxiproxy\Test;

use GuzzleHttp\Client as HttpClient;
use React\EventLoop\Factory as EventLoopFactory,
    React\Dns\Resolver\Factory as DnsResolverFactory,
    React\Socket\Server as SocketServer,
    React\SocketClient\Connector as SocketConnector,
    React\SocketClient\ConnectionException as SocketConnectionException,
    React\Stream\Stream as SocketStream;
use Ihsw\Toxiproxy\Toxiproxy,
    Ihsw\Toxiproxy\Proxy;

abstract class AbstractTest extends \PHPUnit_Framework_TestCase
{
    const TEST_NAME = "ihsw_test_redis_master";
    const TEST_UPSTREAM = "127.0.0.1:6379";
    const TEST_LISTEN = "127.0.0.1:34343";
    const TEST_BASE_URL = "http://127.0.0.1:8474";

    protected static function httpClientFactory()
    {
        return new HttpClient(["base_url" => self::TEST_BASE_URL]);
    }

    public function tearDown()
    {
        $toxiproxy = new Toxiproxy(self::httpClientFactory());
        if ($toxiproxy->exists(self::TEST_NAME)) {
            $toxiproxy->delete($toxiproxy->get(self::TEST_NAME));
        }
    }

    protected function handleProxy(\Closure $callback)
    {
        $toxiproxy = new Toxiproxy(self::httpClientFactory());
        $this->assertTrue(
            $toxiproxy->getHttpClient() instanceof HttpClient,
            "Toxiproxy http-client was not an instance of HttpClient"
        );

        $proxy = $toxiproxy->create(self::TEST_NAME, self::TEST_UPSTREAM, self::TEST_LISTEN);
        $this->assertTrue($proxy instanceof Proxy, "Create proxy was not an instance of Proxy");
        $this->assertEquals(
            $proxy->getHttpResponse()->getStatusCode(),
            Toxiproxy::CREATED,
            sprintf("Could not create proxy '%s' from '%s' to '%s': %s",
                self::TEST_NAME,
                self::TEST_UPSTREAM,
                self::TEST_NAME,
                $proxy->getHttpResponse()->getBody()
            )
        );

        $callback($proxy);
    }

    protected function assertProxyAvailable(Proxy $proxy, $message = null)
    {
        list($ip, $port) = explode(":", $proxy["listen"]);
        $this->assertCanConnect(["ip" => $ip, "port" => $port], $message);
    }

    protected function assertProxyUnavailable(Proxy $proxy, $message = null)
    {
        list($ip, $port) = explode(":", $proxy["listen"]);
        $this->assertCanConnect(["ip" => $ip, "port" => $port, "match" => false], $message);
    }

    public function assertCanConnect(array $options, $message = null)
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
        }, function(SocketConnectionException $e) {
            return false;
        });
        $clientLoop->run();

        // catching the output
        $out = null;
        $promise->done(function($v) use(&$out) {
            $out = $v;
        });

        // optionally cleaning up the server
        if ($settings["startServer"]) {
            $server->shutdown();
        }

        $this->assertEquals($out, $settings["match"], $message);
    }
}