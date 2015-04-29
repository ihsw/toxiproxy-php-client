<?php

use GuzzleHttp\Client as HttpClient;
use Ihsw\Toxiproxy\Toxiproxy,
    Ihsw\Toxiproxy\Client,
    Ihsw\Toxiproxy\Exception\ProxyExistsException,
    Ihsw\Toxiproxy\Exception\NotFoundException;

class ToxiproxyTest extends \PHPUnit_Framework_TestCase
{
    const TEST_NAME = "ihsw_test_redis_master";
    const TEST_UPSTREAM = "localhost:6379";
    CONST TEST_LISTEN = "localhost:34343";

    public function tearDown()
    {
        $toxiproxy = new Toxiproxy();
        $proxies = array_filter($toxiproxy->all(), function($proxy){
            return strlen($proxy["name"]) > 0;
        });
        foreach ($proxies as $proxy)
        {
            $toxiproxy->delete($proxy["name"]);
        }
    }

    public function testGetHttpClient($callback = null)
    {
        $toxiproxy = new Toxiproxy();
        $this->assertTrue($toxiproxy->getHttpClient() instanceof HttpClient, "Toxiproxy http-client was not an instance of HttpClient");

        if (!is_null($callback))
        {
            $callback($toxiproxy);
        }
    }

    public function testCreate($callback = null)
    {
        $this->testGetHttpClient(function(Toxiproxy $toxiproxy) use($callback){
            $response = $toxiproxy->create(self::TEST_NAME, self::TEST_UPSTREAM, self::TEST_LISTEN);
            $this->assertEquals(
                $response->getStatusCode(),
                Toxiproxy::CREATED,
                sprintf("Could not create proxy '%s' from '%s' to '%s': %s",
                    self::TEST_NAME,
                    self::TEST_UPSTREAM,
                    self::TEST_NAME,
                    $response->getBody()
                )
            );

            if (!is_null($callback))
            {
                $callback($toxiproxy);
            }
        });
    }

    public function testGet()
    {
        $this->testCreate(function(Toxiproxy $toxiproxy){
            $response = $toxiproxy->get(self::TEST_NAME);
            $this->assertEquals(
                $response->getStatusCode(),
                Toxiproxy::OK,
                sprintf("Could find proxy '%s': %s", self::TEST_NAME, $response->getBody())
            );
        });
    }

    public function testDelete()
    {
        $this->testCreate(function(Toxiproxy $toxiproxy){
            $response = $toxiproxy->delete(self::TEST_NAME);
            $this->assertEquals(
                $response->getStatusCode(),
                Toxiproxy::NO_CONTENT,
                sprintf("Could not delete proxy '%s': %s", self::TEST_NAME, $response->getBody())
            );
        });
    }
}