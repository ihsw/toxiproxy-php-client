<?php namespace Ihsw\Toxiproxy\Test;

use GuzzleHttp\Client as HttpClient,
    GuzzleHttp\Subscriber\Mock as HttpMock,
    GuzzleHttp\Handler\MockHandler as HttpMockHandler,
    GuzzleHttp\HandlerStack as HttpHandlerStack,
    GuzzleHttp\Psr7 as HttpPsr7,
    GuzzleHttp\Psr7\Response as HttpResponse;
use Ihsw\Toxiproxy\Test\AbstractTest,
    Ihsw\Toxiproxy\Toxiproxy,
    Ihsw\Toxiproxy\Proxy;

abstract class AbstractHttpTest extends AbstractTest
{
    public function tearDown() {}

    /**
     * various factories
     */
    protected static function mockHttpClientFactory(array $responses)
    {
        $mock = new HttpMockHandler($responses);
        $handler = HttpHandlerStack::create($mock);
        $client = new HttpClient(["handler" => $handler]);
        return $client;
    }

    protected static function httpResponseFactory($statusCode, $body, array $headers = [])
    {
        return new HttpResponse($statusCode, $headers, HttpPsr7\stream_for($body));
    }

    protected static function httpTestResponseFactory($statusCode, $filename, array $params = [])
    {
        $contents = file_get_contents(sprintf("%s/tests/test-responses/%s", getcwd(), $filename));
        if (count($params) > 0) {
            $contents = vsprintf($contents, $params);
        }

        return self::httpResponseFactory($statusCode, $contents);
    }

    /**
     * http response templates
     */
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

    /**
     * overriding for explicit http mocking
     */
    protected function handleProxy($responses, \Closure $callback)
    {
        $responses = array_merge([
            self::createProxyResponse(self::TEST_NAME, self::TEST_LISTEN, self::TEST_UPSTREAM)
        ], $responses);
        $httpClient = self::mockHttpClientFactory($responses);
        $toxiproxy = new Toxiproxy($httpClient);

        $proxy = $toxiproxy->create(self::TEST_NAME, self::TEST_UPSTREAM, self::TEST_LISTEN);
        $this->assertTrue($proxy instanceof Proxy, "Create proxy was not an instance of Proxy");

        $callback($proxy);
    }

    protected function assertProxyAvailable(Proxy $proxy, $message = null)
    {
        list($ip, $port) = explode(":", $proxy->getListen());
        $this->assertConnection(["ip" => $ip, "port" => $port, "startServer" => true], $message);
    }

    protected function assertProxyUnavailable(Proxy $proxy, $message = null)
    {
        list($ip, $port) = explode(":", $proxy->getListen());
        $this->assertConnection(["ip" => $ip, "port" => $port, "startServer" => false, "match" => false], $message);
    }
}