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
        $this->handleProxy(function(Proxy $proxy){
            $response = $proxy->update("fdsfgs", "downstream", []);
            $this->assertEquals(
                $response->getStatusCode(),
                Toxiproxy::OK,
                sprintf("Could not update downstream fdsfgs toxic for proxy '%s'", $proxy["name"])
            );
        });
    }

    /**
     * @expectedException Ihsw\Toxiproxy\Exception\InvalidToxicException
     */
    public function testUpdateInvalidToxicDirection()
    {
        $this->handleProxy(function(Proxy $proxy){
            $response = $proxy->update("latency", "fdsfgs", []);
            $this->assertEquals(
                $response->getStatusCode(),
                Toxiproxy::OK,
                sprintf("Could not update fdsfgs latency toxic for proxy '%s'", $proxy["name"])
            );
        });
    }
}