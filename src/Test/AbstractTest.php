<?php namespace Ihsw\Toxiproxy\Test;

use GuzzleHttp\Client as HttpClient;
use Ihsw\Toxiproxy\Toxiproxy,
    Ihsw\Toxiproxy\Proxy;

abstract class AbstractTest extends \PHPUnit_Framework_TestCase
{
    const TEST_NAME = "ihsw_test_redis_master";
    const TEST_UPSTREAM = "localhost:6379";
    const TEST_LISTEN = "localhost:34343";

    public function tearDown()
    {
        $toxiproxy = new Toxiproxy();
        if ($toxiproxy->exists(self::TEST_NAME)) {
            $toxiproxy->delete($toxiproxy->get(self::TEST_NAME));
        }
    }

    public function handleProxy(\Closure $callback)
    {
        $toxiproxy = new Toxiproxy();
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
}