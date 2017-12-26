<?php

namespace Ihsw\ToxyproxyTests\Integration;

use Ihsw\Toxiproxy\Test\AbstractTest;
use Ihsw\Toxiproxy\Toxiproxy;
use Ihsw\Toxiproxy\Proxy;

class ProxyTest extends AbstractTest
{
    /**
     * @expectedException Ihsw\Toxiproxy\Exception\InvalidToxicException
     */
    public function testUpdateInvalidToxic()
    {
        $this->handleProxy(function (Proxy $proxy) {
            $proxy->updateDownstream("fdsfgs", []);
        });
    }

    public function testDisable($callback = null)
    {
        $this->handleProxy(function (Proxy $proxy) use ($callback) {
            $response = $proxy->disable();
            $this->assertEquals(
                $response->getStatusCode(),
                Toxiproxy::OK,
                sprintf("Could not disable proxy '%s': %s", $proxy->getName(), $response->getBody())
            );

            $this->assertProxyUnavailable(
                $proxy,
                sprintf("Could not verify proxy '%s' being unavailable", $proxy->getName())
            );

            if (!is_null($callback)) {
                $callback($proxy);
            }
        });
    }

    public function testEnable()
    {
        $this->testDisable(function (Proxy $proxy) {
            $response = $proxy->enable();
            $this->assertEquals(
                $response->getStatusCode(),
                Toxiproxy::OK,
                sprintf("Could not enable proxy '%s': %s", $proxy->getName(), $response->getBody())
            );

            $this->assertProxyAvailable(
                $proxy,
                sprintf("Could not verify proxy '%s' being available", $proxy->getName())
            );
        });
    }

    public function testCreateArrayAccess()
    {
        $this->handleProxy(function (Proxy $proxy) {
            $this->markTestIncomplete("NYI");
            $proxy["test"] = "test";
        });
    }

    public function testGetArrayAccess()
    {
        $this->handleProxy(function (Proxy $proxy) {
            $this->markTestIncomplete("NYI");
            $test = $proxy["test"];
        });
    }

    public function testGetNonexistArrayAccess()
    {
        $this->handleProxy(function (Proxy $proxy) {
            $this->markTestIncomplete("NYI");
            unset($proxy["test-non-exist"]);
        });
    }

    public function testDeleteArrayAccess()
    {
        $this->handleProxy(function (Proxy $proxy) {
            $this->markTestIncomplete("NYI");
            unset($proxy["test"]);
        });
    }
}
