<?php

use GuzzleHttp\Client as HttpClient;
use Ihsw\Toxiproxy\Toxiproxy,
    Ihsw\Toxiproxy\Client,
    Ihsw\Toxiproxy\Exception\ProxyExistsException,
    Ihsw\Toxiproxy\Exception\NotFoundException,
    Ihsw\Toxiproxy\Proxy;

class ToxiproxyTest extends \PHPUnit_Framework_TestCase
{
    const TEST_NAME = "ihsw_test_redis_master";
    const TEST_UPSTREAM = "localhost:6379";
    CONST TEST_LISTEN = "localhost:34343";
    const NONEXISTENT_TEST_NAME = "ihsw_test_redis_nonexist";

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

            if (!is_null($callback))
            {
                $callback($toxiproxy, $proxy);
            }
        });
    }

    public function testAll()
    {
        $this->testCreate(function(Toxiproxy $toxiproxy){
            $result = array_reduce($toxiproxy->all(), function($result, $proxy){
                if (!$proxy) {
                    return $proxy;
                }
                return $proxy instanceof Proxy;
            }, true);
            $this->assertTrue($result, "All results were not instances of Proxy");
        });
    }

    public function testCreateArrayAccess()
    {
        $this->testGetHttpClient(function(Toxiproxy $toxiproxy){
            $toxiproxy[self::TEST_NAME] = [self::TEST_UPSTREAM, self::TEST_LISTEN];
            $proxy = $toxiproxy[self::TEST_NAME];
            $this->assertTrue($proxy instanceof Proxy, "Create proxy was not an instance of Proxy");
            $this->assertEquals(
                $proxy->getHttpResponse()->getStatusCode(),
                Toxiproxy::OK,
                sprintf("Could not create proxy '%s' from '%s' to '%s': %s",
                    self::TEST_NAME,
                    self::TEST_UPSTREAM,
                    self::TEST_NAME,
                    $proxy->getHttpResponse()->getBody()
                )
            );
        });
    }

    /**
     * @expectedException Ihsw\Toxiproxy\Exception\ProxyExistsException
     */
    public function testCreateDuplicate()
    {
        $this->testCreate(function(Toxiproxy $toxiproxy, $proxy){
            $toxiproxy->create($proxy["name"], $proxy["upstream"], $proxy["listen"]);
        });
    }

    public function testGet()
    {
        $this->testCreate(function(Toxiproxy $toxiproxy, $proxy){
            $proxy = $toxiproxy->get($proxy["name"]);
            $this->assertTrue($proxy instanceof Proxy, "Create proxy was not an instance of Proxy");
            $this->assertEquals(
                $proxy->getHttpResponse()->getStatusCode(),
                Toxiproxy::OK,
                sprintf("Could find proxy '%s': %s", $proxy["name"], $proxy->getHttpResponse()->getBody())
            );
        });
    }

    public function testGetArrayAccess()
    {
        $this->testCreate(function(Toxiproxy $toxiproxy, $proxy){
            $proxy = $toxiproxy[$proxy["name"]];
            $this->assertTrue($proxy instanceof Proxy, "Create proxy was not an instance of Proxy");
            $this->assertEquals(
                $proxy->getHttpResponse()->getStatusCode(),
                Toxiproxy::OK,
                sprintf("Could find proxy '%s': %s", $proxy["name"], $proxy->getHttpResponse()->getBody())
            );
        });
    }

    /**
     * @expectedException Ihsw\Toxiproxy\Exception\NotFoundException
     */
    public function testGetNonexist()
    {
        $this->testGetHttpClient(function(Toxiproxy $toxiproxy){
            $toxiproxy->get(self::NONEXISTENT_TEST_NAME);
        });
    }

    public function testGetNonexistArrayAccess()
    {
        $this->testGetHttpClient(function(Toxiproxy $toxiproxy){
            $this->assertFalse(array_key_exists(self::NONEXISTENT_TEST_NAME, $toxiproxy));
        });
    }

    public function testDelete()
    {
        $this->testCreate(function(Toxiproxy $toxiproxy, $proxy){
            $response = $toxiproxy->delete($proxy["name"]);
            $this->assertEquals(
                $response->getStatusCode(),
                Toxiproxy::NO_CONTENT,
                sprintf("Could not delete proxy '%s': %s", $proxy["name"], $response->getBody())
            );
        });
    }

    public function testDeleteArrayAccess()
    {
        $this->testCreate(function(Toxiproxy $toxiproxy, $proxy){
            unset($toxiproxy[$proxy["name"]]);
            $this->assertFalse(
                array_key_exists($proxy["name"], $toxiproxy),
                Toxiproxy::NO_CONTENT,
                sprintf("Could not delete proxy '%s'", $proxy["name"])
            );
        });
    }
}