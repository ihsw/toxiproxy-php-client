<?php

use GuzzleHttp\Client as HttpClient;
use Ihsw\Toxiproxy\Test\AbstractTest,
    Ihsw\Toxiproxy\Toxiproxy,
    Ihsw\Toxiproxy\Proxy;

class TimeoutTest extends AbstractTest
{
    public function testUpdateDownstream()
    {
        $this->handleProxy(function(Proxy $proxy) {
            $response = $proxy->updateDownstream("timeout", ["timeout" => 1000]);
            $this->assertEquals(
                $response->getStatusCode(),
                Toxiproxy::OK,
                sprintf("Could not update downstream timeout toxic for proxy '%s'", $proxy->getName())
            );
        });
    }

    public function testUpdateUpstream()
    {
        $this->handleProxy(function(Proxy $proxy) {
            $response = $proxy->updateUpstream("timeout", ["timeout" => 1000]);
            $this->assertEquals(
                $response->getStatusCode(),
                Toxiproxy::OK,
                sprintf("Could not update upstream timeout toxic for proxy '%s'", $proxy->getName())
            );
        });
    }
}