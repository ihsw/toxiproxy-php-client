<?php

use Ihsw\Toxiproxy\Test\AbstractHttpTest,
    Ihsw\Toxiproxy\Toxiproxy,
    Ihsw\Toxiproxy\Proxy;

class ToxiproxyTest extends AbstractHttpTest
{
    const NONEXISTENT_TEST_NAME = "ihsw_test_redis_nonexist";

    public function testCreate(array $responses = [], $callback = null)
    {
        $responses = array_merge(
            [self::createProxyResponse(self::TEST_NAME, self::TEST_LISTEN, self::TEST_UPSTREAM)],
            $responses
        );
        $toxiproxy = new Toxiproxy(self::mockHttpClientFactory($responses));

        $proxy = $toxiproxy->create(self::TEST_NAME, self::TEST_UPSTREAM, self::TEST_LISTEN);
        $this->assertTrue($proxy instanceof Proxy, "Create proxy was not an instance of Proxy");

        if (!is_null($callback)) {
            $callback($toxiproxy, $proxy);
        }
    }

    public function testAll()
    {
        $responses = [
            self::httpTestResponseFactory(
                Toxiproxy::CREATED,
                "all.json",
                [self::TEST_NAME, self::TEST_NAME, self::TEST_LISTEN, self::TEST_UPSTREAM]
            )
        ];

        $this->testCreate($responses, function(Toxiproxy $toxiproxy) {
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
        $responses = [
            self::createProxyResponse(self::TEST_NAME, self::TEST_UPSTREAM, self::TEST_LISTEN),
            self::getProxyResponse(self::TEST_NAME, self::TEST_LISTEN, self::TEST_UPSTREAM)
        ];
        $toxiproxy = new Toxiproxy(self::mockHttpClientFactory($responses));

        $toxiproxy[self::TEST_NAME] = [self::TEST_UPSTREAM, self::TEST_LISTEN];
        $proxy = $toxiproxy[self::TEST_NAME];
        $this->assertTrue($proxy instanceof Proxy, "Create proxy was not an instance of Proxy");
    }

    /**
     * @expectedException Ihsw\Toxiproxy\Exception\ProxyExistsException
     */
    public function testCreateDuplicate()
    {
        $responses = [
            self::httpTestResponseFactory(
                Toxiproxy::CONFLICT,
                "get-proxy.json",
                [self::TEST_NAME, self::TEST_LISTEN, self::TEST_UPSTREAM]
            )
        ];
        $this->testCreate($responses, function(Toxiproxy $toxiproxy, Proxy $proxy) {
            $toxiproxy->create($proxy->getName(), $proxy->getUpstream(), $proxy->getListen());
        });
    }

    public function testGet()
    {
        $responses = [self::getProxyResponse(self::TEST_NAME, self::TEST_LISTEN, self::TEST_UPSTREAM)];
        $this->testCreate($responses, function(Toxiproxy $toxiproxy, Proxy $proxy) {
            $proxy = $toxiproxy->get($proxy->getName());
            $this->assertTrue($proxy instanceof Proxy, "Create proxy was not an instance of Proxy");
        });
    }

    public function testExists()
    {
        $responses = [self::getProxyResponse(self::TEST_NAME, self::TEST_LISTEN, self::TEST_UPSTREAM)];
        $this->testCreate($responses, function(Toxiproxy $toxiproxy, Proxy $proxy) {
            $exists = $toxiproxy->exists($proxy->getName());
            $this->assertTrue($exists, "Exists was not true");
        });
    }

    public function testNotExists()
    {
        $responses = [self::getNonexistentProxyResponse(self::TEST_NAME)];
        $toxiproxy = new Toxiproxy(self::mockHttpClientFactory($responses));
        $exists = $toxiproxy->exists(self::NONEXISTENT_TEST_NAME);
        $this->assertFalse($exists, "Exists was not false");
    }

    public function testGetArrayAccess()
    {
        $responses = [self::getProxyResponse(self::TEST_NAME, self::TEST_LISTEN, self::TEST_UPSTREAM)];
        $this->testCreate($responses, function(Toxiproxy $toxiproxy, Proxy $proxy) {
            $proxy = $toxiproxy[$proxy->getName()];
            $this->assertTrue($proxy instanceof Proxy, "Create proxy was not an instance of Proxy");
        });
    }

    public function testGetNonexist()
    {
        $toxiproxy = new Toxiproxy(self::mockHttpClientFactory(
            [self::getNonexistentProxyResponse(self::NONEXISTENT_TEST_NAME)]
        ));
        $proxy = $toxiproxy->get(self::NONEXISTENT_TEST_NAME);
        $this->assertNull($proxy, "Non-existent proxy was expected to be null, was not null");
    }

    public function testGetNonexistArrayAccess()
    {
        $toxiproxy = new Toxiproxy(self::mockHttpClientFactory(
            [self::getNonexistentProxyResponse(self::NONEXISTENT_TEST_NAME)]
        ));
        $proxy = $toxiproxy[self::NONEXISTENT_TEST_NAME];
        $this->assertNull($proxy, "Non-existent proxy was expected to be null, was not null");
    }

    public function testDelete()
    {
        $responses = [self::httpResponseFactory(Toxiproxy::NO_CONTENT, "")];
        $this->testCreate($responses, function(Toxiproxy $toxiproxy, Proxy $proxy) {
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
        $responses = [self::httpResponseFactory(Toxiproxy::NO_CONTENT, "")];
        $this->testCreate($responses, function(Toxiproxy $toxiproxy, Proxy $proxy) {
            unset($toxiproxy[$proxy]);
            $this->assertFalse(
                array_key_exists($proxy->getName(), $toxiproxy),
                sprintf("Could not delete proxy '%s'", $proxy->getName())
            );
        });
    }

    public function testReset()
    {
        $responses = [
            self::disableProxyResponse(self::TEST_NAME, self::TEST_UPSTREAM, self::TEST_LISTEN),
            self::httpResponseFactory(Toxiproxy::NO_CONTENT, "")
        ];
        $this->testCreate($responses, function(Toxiproxy $toxiproxy, Proxy $proxy) {
            $response = $proxy->disable();
            $this->assertProxyUnavailable(
                $proxy,
                sprintf("Could not verify proxy '%s' being unavailable", $proxy->getName())
            );

            $toxiproxy->reset();
            $this->assertProxyAvailable(
                $proxy,
                sprintf("Could not verify proxy '%s' being available", $proxy->getName())
            );
        });
    }
}