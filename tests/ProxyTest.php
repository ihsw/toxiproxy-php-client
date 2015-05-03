<?php

use GuzzleHttp\Client as HttpClient;
use Ihsw\Toxiproxy\Test\AbstractTest,
    Ihsw\Toxiproxy\Toxiproxy,
    Ihsw\Toxiproxy\Proxy;

class ProxyTest extends AbstractTest
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

            list($ip, $port) = explode(":", $proxy["listen"]);
            $this->assertFalse(
                $this->canConnect($ip, $port),
                sprintf("Could not verify disabling proxy '%s'", $proxy["name"])
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

            list($ip, $port) = explode(":", $proxy["listen"]);
            $this->assertTrue(
                $this->canConnect($ip, $port),
                sprintf("Could not verify enabling proxy '%s'", $proxy["name"])
            );
        });
    }
}