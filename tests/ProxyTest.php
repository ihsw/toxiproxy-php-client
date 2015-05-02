<?php

use GuzzleHttp\Client as HttpClient;
use Ihsw\Toxiproxy\Toxiproxy,
    Ihsw\Toxiproxy\Proxy;

class ProxyTest extends \PHPUnit_Framework_TestCase
{
    const TEST_NAME = "ihsw_test_redis_master";
    const TEST_UPSTREAM = "localhost:6379";
    CONST TEST_LISTEN = "localhost:34343";

    const TEST_TOXIC = "latency";
    const TEST_DIRECTION = "downstream";

    public function tearDown()
    {
        $toxiproxy = new Toxiproxy();
        $proxies = array_filter($toxiproxy->all(), function($proxy){
            return strlen($proxy["name"]) > 0;
        });
        foreach ($proxies as $proxy) {
            $toxiproxy->delete($proxy);
        }
    }

    public function handleProxy(\Closure $callback)
    {
        $toxiproxy = new Toxiproxy();
        $this->assertTrue($toxiproxy->getHttpClient() instanceof HttpClient, "Toxiproxy http-client was not an instance of HttpClient");

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

    public function testUpdate()
    {
        $this->handleProxy(function(Proxy $proxy){
            $response = $proxy->update(self::TEST_TOXIC, self::TEST_DIRECTION, ["latency" => 100]);
            $this->assertEquals(
                $response->getStatusCode(),
                Toxiproxy::OK,
                sprintf("Could not update toxic '%s' for proxy '%s' in direction '%s'",
                    self::TEST_TOXIC,
                    $proxy["name"],
                    self::TEST_DIRECTION
                )
            );
        });
    }
}