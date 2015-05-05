<?php

use GuzzleHttp\Client as HttpClient;
use Ihsw\Toxiproxy\Test\AbstractHttpTest,
    Ihsw\Toxiproxy\Toxiproxy,
    Ihsw\Toxiproxy\Proxy;

class ProxyTest extends AbstractHttpTest
{
    /**
     * @expectedException Ihsw\Toxiproxy\Exception\InvalidToxicException
     */
    public function testUpdateInvalidToxic()
    {
        $this->handleProxy(function(Proxy $proxy) {
            $response = $proxy->updateDownstream("fdsfgs", []);
            $this->assertEquals(
                $response->getStatusCode(),
                Toxiproxy::OK,
                sprintf("Could not update downstream fdsfgs toxic for proxy '%s'", $proxy["name"])
            );
        });
    }

    public function testDisable($callback = null)
    {
        $this->handleProxy(function(Proxy $proxy) use($callback) {
            $response = $proxy->disable();
            $this->assertEquals(
                $response->getStatusCode(),
                Toxiproxy::OK,
                sprintf("Could not disable proxy '%s': %s", $proxy["name"], $response->getBody())
            );

            $this->assertProxyUnavailable(
                $proxy,
                sprintf("Could not verify proxy '%s' being unavailable", $proxy["name"])
            );

            if (!is_null($callback)) {
                $callback($proxy);
            }
        });
    }

    public function testEnable()
    {
        $this->testDisable(function(Proxy $proxy) {
            $response = $proxy->enable();
            $this->assertEquals(
                $response->getStatusCode(),
                Toxiproxy::OK,
                sprintf("Could not enable proxy '%s': %s", $proxy["name"], $response->getBody())
            );

            $this->assertProxyAvailable(
                $proxy,
                sprintf("Could not verify proxy '%s' being available", $proxy["name"])
            );
        });
    }
}