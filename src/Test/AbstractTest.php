<?php namespace Ihsw\Toxiproxy\Test;

use GuzzleHttp\Client as HttpClient;
use React\EventLoop\Factory as EventLoopFactory,
    React\Dns\Resolver\Factory as DnsResolverFactory,
    React\SocketClient\Connector as ClientConnector,
    React\SocketClient\ConnectionException,
    React\Stream\Stream;
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

    protected function assertProxyAvailable(Proxy $proxy, $message = "")
    {
        list($ip, $port) = explode(":", $proxy["listen"]);
        $this->assertTrue($this->canConnect($ip, $port), $message);
    }

    protected function assertProxyUnavailable(Proxy $proxy, $message = "")
    {
        list($ip, $port) = explode(":", $proxy["listen"]);
        $this->assertFalse($this->canConnect($ip, $port), $message);
    }

    protected function canConnect($ip, $port)
    {
        // misc
        $loop = EventLoopFactory::create();
        $dnsResolverFactory = new DnsResolverFactory();
        $dns = $dnsResolverFactory->createCached("8.8.8.8", $loop); // dunno why dns is required for this shit
        $connector = new ClientConnector($loop, $dns);

        // socket loop definition
        $promise = $connector->create($ip, $port)->then(function (Stream $stream) {
            $stream->close();
            return true;
        }, function(ConnectionException $e) {
            return false;
        });

        // starting it up
        $loop->run();

        // catching the output
        $out = null;
        $promise->done(function($v) use(&$out) {
            $out = $v;
        });

        return $out;
    }
}