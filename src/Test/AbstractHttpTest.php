<?php namespace Ihsw\Toxiproxy\Test;

use GuzzleHttp\Subscriber\Mock as HttpMock,
    GuzzleHttp\Stream\Stream as HttpStream,
    GuzzleHttp\Message\Response as HttpResponse;
use Ihsw\Toxiproxy\Test\AbstractTest,
    Ihsw\Toxiproxy\Toxiproxy,
    Ihsw\Toxiproxy\Proxy;

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

    protected function assertProxyAvailable(Proxy $proxy, $message = null)
    {
        list($ip, $port) = explode(":", $proxy["listen"]);
        $this->assertCanConnect(["ip" => $ip, "port" => $port, "startServer" => true], $message);
    }

    protected function assertProxyUnavailable(Proxy $proxy, $message = null)
    {
        list($ip, $port) = explode(":", $proxy["listen"]);
        $this->assertCanConnect(["ip" => $ip, "port" => $port, "startServer" => true, "match" => false], $message);
    }
}