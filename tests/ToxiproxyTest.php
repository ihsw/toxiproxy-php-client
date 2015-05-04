<?php

use GuzzleHttp\Client as HttpClient;
use Ihsw\Toxiproxy\Test\AbstractTest,
    Ihsw\Toxiproxy\Toxiproxy,
    Ihsw\Toxiproxy\Exception\ProxyExistsException,
    Ihsw\Toxiproxy\Exception\NotFoundException,
    Ihsw\Toxiproxy\Proxy;

class ToxiproxyTest extends AbstractTest
{
    const NONEXISTENT_TEST_NAME = "ihsw_test_redis_nonexist";

    public function testGetHttpClient(\Closure $callback = null)
    {
        $toxiproxy = new Toxiproxy(self::TEST_BASE_URL);
        $this->assertTrue(
            $toxiproxy->getHttpClient() instanceof HttpClient,
            "Toxiproxy http-client was not an instance of HttpClient"
        );

        if (!is_null($callback)) {
            $callback($toxiproxy);
        }
    }

    public function testCreate($callback = null)
    {
        $this->testGetHttpClient(function(Toxiproxy $toxiproxy) use($callback) {
            $proxy = $toxiproxy->create(self::TEST_NAME, self::TEST_UPSTREAM, self::TEST_LISTEN);
            $this->assertTrue($proxy instanceof Proxy, "Create proxy was not an instance of Proxy");
            $this->assertEquals(
                $proxy->getHttpResponse()->getStatusCode(),
                Toxiproxy::CREATED,
                sprintf("Could not create proxy '%s' from '%s' to '%s': %s",
                    self::TEST_NAME,
                    self::TEST_UPSTREAM,
                    self::TEST_LISTEN,
                    $proxy->getHttpResponse()->getBody()
                )
            );

            if (!is_null($callback)) {
                $callback($toxiproxy, $proxy);
            }
        });
    }

    public function testAll()
    {
        $this->testCreate(function(Toxiproxy $toxiproxy) {
            $result = array_reduce($toxiproxy->all(), function($result, $proxy) {
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
        $this->testGetHttpClient(function(Toxiproxy $toxiproxy) {
            $toxiproxy[self::TEST_NAME] = [self::TEST_UPSTREAM, self::TEST_LISTEN];
            $proxy = $toxiproxy[self::TEST_NAME];
            $this->assertTrue($proxy instanceof Proxy, "Create proxy was not an instance of Proxy");
            $this->assertEquals(
                $proxy->getHttpResponse()->getStatusCode(),
                Toxiproxy::OK,
                sprintf("Could not create proxy '%s' from '%s' to '%s': %s",
                    self::TEST_NAME,
                    self::TEST_UPSTREAM,
                    self::TEST_LISTEN,
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
        $this->testCreate(function(Toxiproxy $toxiproxy, Proxy $proxy) {
            $toxiproxy->create($proxy["name"], $proxy["upstream"], $proxy["listen"]);
        });
    }

    public function testGet()
    {
        $this->testCreate(function(Toxiproxy $toxiproxy, Proxy $proxy) {
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
        $this->testCreate(function(Toxiproxy $toxiproxy, Proxy $proxy) {
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
        $this->testGetHttpClient(function(Toxiproxy $toxiproxy) {
            $toxiproxy->get(self::NONEXISTENT_TEST_NAME);
        });
    }

    public function testGetNonexistArrayAccess()
    {
        $this->testGetHttpClient(function(Toxiproxy $toxiproxy) {
            $this->assertFalse(array_key_exists(self::NONEXISTENT_TEST_NAME, $toxiproxy));
        });
    }

    public function testDelete()
    {
        $this->testCreate(function(Toxiproxy $toxiproxy, Proxy $proxy) {
            $response = $toxiproxy->delete($proxy);
            $this->assertEquals(
                $response->getStatusCode(),
                Toxiproxy::NO_CONTENT,
                sprintf("Could not delete proxy '%s': %s", $proxy["name"], $response->getBody())
            );
        });
    }

    public function testDeleteArrayAccess()
    {
        $this->testCreate(function(Toxiproxy $toxiproxy, Proxy $proxy) {
            unset($toxiproxy[$proxy]);
            $this->assertFalse(
                array_key_exists($proxy["name"], $toxiproxy),
                sprintf("Could not delete proxy '%s'", $proxy["name"])
            );
        });
    }

    public function testReset()
    {
        $this->testCreate(function(Toxiproxy $toxiproxy, Proxy $proxy) {
            $response = $proxy->updateDownstream("latency", ["enabled" => true, "latency" => 1000]);
            $this->assertEquals(
                $response->getStatusCode(),
                Toxiproxy::OK,
                sprintf("Could not update downstream latency toxic for proxy '%s'", $proxy["name"])
            );

            $proxy->disable();
            $this->assertProxyUnavailable($proxy);

            $toxiproxy->reset();
            $this->assertProxyAvailable($proxy);
        });
    }
}