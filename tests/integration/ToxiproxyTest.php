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

    public function testCreate($callback = null)
    {
        $toxiproxy = new Toxiproxy(self::httpClientFactory());

        $proxy = $toxiproxy->create(self::TEST_NAME, self::TEST_UPSTREAM, self::TEST_LISTEN);
        $this->assertTrue($proxy instanceof Proxy, "Create proxy was not an instance of Proxy");

        if (!is_null($callback)) {
            $callback($toxiproxy, $proxy);
        }
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
        $toxiproxy = new Toxiproxy(self::httpClientFactory());

        $toxiproxy[self::TEST_NAME] = [self::TEST_UPSTREAM, self::TEST_LISTEN];
        $proxy = $toxiproxy[self::TEST_NAME];
        $this->assertTrue($proxy instanceof Proxy, "Create proxy was not an instance of Proxy");
    }

    /**
     * @expectedException Ihsw\Toxiproxy\Exception\ProxyExistsException
     */
    public function testCreateDuplicate()
    {
        $this->testCreate(function(Toxiproxy $toxiproxy, Proxy $proxy) {
            $toxiproxy->create($proxy->getName(), $proxy->getUpstream(), $proxy->getListen());
        });
    }

    public function testGet()
    {
        $this->testCreate(function(Toxiproxy $toxiproxy, Proxy $proxy) {
            $proxy = $toxiproxy->get($proxy->getName());
            $this->assertTrue($proxy instanceof Proxy, "Create proxy was not an instance of Proxy");
        });
    }

    public function testExists()
    {
        $this->testCreate(function(Toxiproxy $toxiproxy, Proxy $proxy) {
            $exists = $toxiproxy->exists($proxy->getName());
            $this->assertTrue($exists, "Exists was not true");
        });
    }

    public function testNotExists()
    {
        $toxiproxy = new Toxiproxy(self::httpClientFactory());
        $exists = $toxiproxy->exists(self::NONEXISTENT_TEST_NAME);
        $this->assertFalse($exists, "Exists was not false");
    }

    public function testGetArrayAccess()
    {
        $this->testCreate(function(Toxiproxy $toxiproxy, Proxy $proxy) {
            $proxy = $toxiproxy[$proxy->getName()];
            $this->assertTrue($proxy instanceof Proxy, "Create proxy was not an instance of Proxy");
        });
    }

    public function testGetNonexist()
    {
        $toxiproxy = new Toxiproxy(self::httpClientFactory());
        $proxy = $toxiproxy->get(self::NONEXISTENT_TEST_NAME);
        $this->assertNull($proxy, sprintf("Non-existent proxy was not null", self::NONEXISTENT_TEST_NAME));
    }

    public function testGetNonexistArrayAccess()
    {
        $toxiproxy = new Toxiproxy(self::httpClientFactory());
        $proxy = $toxiproxy[self::NONEXISTENT_TEST_NAME];
        $this->assertNull($proxy, sprintf("Non-existent proxy was not null", self::NONEXISTENT_TEST_NAME));
    }

    public function testDelete()
    {
        $this->testCreate(function(Toxiproxy $toxiproxy, Proxy $proxy) {
            $response = $toxiproxy->delete($proxy);
            $this->assertEquals(
                $response->getStatusCode(),
                Toxiproxy::NO_CONTENT,
                sprintf("Could not delete proxy '%s': %s", $proxy->getName(), $response->getBody())
            );
        });
    }

    public function testDeleteArrayAccess()
    {
        $this->testCreate(function(Toxiproxy $toxiproxy, Proxy $proxy) {
            unset($toxiproxy[$proxy]);
            $this->assertFalse(
                array_key_exists($proxy->getName(), $toxiproxy),
                sprintf("Could not delete proxy '%s'", $proxy->getName())
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
                sprintf("Could not update downstream latency toxic for proxy '%s'", $proxy->getName())
            );

            $proxy->disable();
            $this->assertProxyUnavailable($proxy);

            $toxiproxy->reset();
            $this->assertProxyAvailable($proxy);
        });
    }
}