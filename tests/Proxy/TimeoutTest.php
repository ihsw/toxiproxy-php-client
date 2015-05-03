<?php

use GuzzleHttp\Client as HttpClient;
use Ihsw\Toxiproxy\Test\AbstractTest,
    Ihsw\Toxiproxy\Toxiproxy,
    Ihsw\Toxiproxy\Proxy;

class TimeoutTest extends AbstractTest
{
    public function testUpdateTimeoutDownstream()
    {
        $this->handleProxy(function(Proxy $proxy) {
            $response = $proxy->updateDownstream("timeout", ["timeout" => 1000]);
            $this->assertEquals(
                $response->getStatusCode(),
                Toxiproxy::OK,
                sprintf("Could not update downstream timeout toxic for proxy '%s'", $proxy["name"])
            );
        });
    }

    public function testUpdateTimeoutUpstream()
    {
        $this->handleProxy(function(Proxy $proxy) {
            $response = $proxy->updateUpstream("timeout", ["timeout" => 1000]);
            $this->assertEquals(
                $response->getStatusCode(),
                Toxiproxy::OK,
                sprintf("Could not update upstream timeout toxic for proxy '%s'", $proxy["name"])
            );
        });
    }
}