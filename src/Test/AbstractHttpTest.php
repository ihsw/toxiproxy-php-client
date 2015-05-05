<?php namespace Ihsw\Toxiproxy\Test;

use GuzzleHttp\Subscriber\Mock as HttpMock,
    GuzzleHttp\Client as HttpClient,
    GuzzleHttp\Stream\Stream as HttpStream,
    GuzzleHttp\Message\Response as HttpResponse;
use Ihsw\Toxiproxy\Test\AbstractTest,
    Ihsw\Toxiproxy\Toxiproxy,
    Ihsw\Toxiproxy\Proxy;

abstract class AbstractHttpTest extends AbstractTest
{
    // public function tearDown() {}

    protected static function mockHttpClientFactory(array $contentList = [])
    {
        $httpClient = self::httpClientFactory();
        $mock = new HttpMock(array_map(function($content) {
            $content = array_merge(["statusCode" => -1, "headers" => [], "body" => ""], $content);
            return new HttpResponse(
                $content["statusCode"],
                $content["headers"],
                HttpStream::factory($content["body"]
            ));
        }, $contentList));
        $httpClient->getEmitter()->attach($mock);
        return $httpClient;
    }

    protected static function getTestResponse($filename, $params)
    {
        return vsprintf(file_get_contents(sprintf("%s/tests/test-responses/%s", getcwd(), $filename)), $params);
    }

    protected function handleProxy(\Closure $callback)
    {
        $httpClient = self::mockHttpClientFactory([
            [
                "statusCode" => Toxiproxy::CREATED,
                "body" => self::getTestResponse(
                    "create-proxy.json",
                    [self::TEST_NAME, self::TEST_UPSTREAM, self::TEST_LISTEN]
                )
            ]
        ]);
        $httpClient = self::httpClientFactory();
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
}