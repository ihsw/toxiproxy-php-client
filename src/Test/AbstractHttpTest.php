<?php namespace Ihsw\Toxiproxy\Test;

use GuzzleHttp\Subscriber\Mock as HttpMock,
    GuzzleHttp\Stream\Stream as HttpStream,
    GuzzleHttp\Message\Response as HttpResponse;
use Ihsw\Toxiproxy\Test\AbstractTest,
    Ihsw\Toxiproxy\Toxiproxy,
    Ihsw\Toxiproxy\Proxy;
use React\EventLoop\Factory as EventLoopFactory,
    React\Dns\Resolver\Factory as DnsResolverFactory,
    React\Socket\Server as SocketServer,
    React\SocketClient\Connector as SocketConnector,
    React\SocketClient\ConnectionException as SocketConnectionException,
    React\Stream\Stream as SocketStream;

abstract class AbstractHttpTest extends AbstractTest
{
    public function tearDown() {}

    protected static function mockHttpClientFactory(array $responses)
    {
        $httpClient = self::httpClientFactory();
        $mock = new HttpMock($responses);
        $httpClient->getEmitter()->attach($mock);
        return $httpClient;
    }

    protected static function httpResponseFactory($statusCode, $body, array $headers = [])
    {
        return new HttpResponse($statusCode, $headers, HttpStream::factory($body));
    }

    protected static function httpTestResponseFactory($statusCode, $filename, array $params)
    {
        return self::httpResponseFactory(
            $statusCode,
            vsprintf(file_get_contents(sprintf("%s/tests/test-responses/%s", getcwd(), $filename)), $params)
        );
    }

    protected static function createProxyResponse($name, $listen, $upstream)
    {
        return self::httpTestResponseFactory(Toxiproxy::CREATED, "get-proxy.json", [$name, $listen, $upstream]);
    }

    protected static function getProxyResponse($name, $listen, $upstream)
    {
        return self::httpTestResponseFactory(Toxiproxy::OK, "get-proxy.json", [$name, $listen, $upstream]);
    }

    protected static function getNonexistentProxyResponse($name)
    {
        return self::httpTestResponseFactory(Toxiproxy::NOT_FOUND, "get-nonexistent-proxy.json", [$name]);
    }

    protected static function disableProxyResponse($name, $upstream, $listen)
    {
        return self::httpTestResponseFactory(Toxiproxy::OK, "disable-proxy.json", [$name, $listen, $upstream]);
    }

    protected static function enableProxyResponse($name, $upstream, $listen)
    {
        return self::httpTestResponseFactory(Toxiproxy::OK, "get-proxy.json", [$name, $listen, $upstream]);
    }

    protected function handleProxy($responses, \Closure $callback)
    {
        $responses = array_merge([
            self::createProxyResponse(self::TEST_NAME, self::TEST_LISTEN, self::TEST_UPSTREAM)
        ], $responses);
        $httpClient = self::mockHttpClientFactory($responses);
        $toxiproxy = new Toxiproxy($httpClient);

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

    private function createDnsResolverMock()
    {
        return $this->getMockBuilder("React\Dns\Resolver\Resolver")
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function canConnect($ip, $port)
    {
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

        return $out;
    }
}