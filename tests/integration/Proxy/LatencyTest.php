<?php

use GuzzleHttp\Client as HttpClient;
use Ihsw\Toxiproxy\Test\AbstractTest,
    Ihsw\Toxiproxy\Toxiproxy,
    Ihsw\Toxiproxy\Proxy;

class LatencyTest extends AbstractTest
{
    public function testUpdateDownstream()
    {
        $this->handleProxy(function(Proxy $proxy) {
            $response = $proxy->updateDownstream("latency", ["latency" => 100, "jitter" => 100]);
            $this->assertEquals(
                $response->getStatusCode(),
                Toxiproxy::OK,
                sprintf("Could not update downstream latency toxic for proxy '%s'", $proxy->getName())
            );
        });
    }

    public function testUpdateUpstream()
    {
        $this->handleProxy(function(Proxy $proxy) {
            $response = $proxy->updateUpstream("latency", ["latency" => 100, "jitter" => 100]);
            $this->assertEquals(
                $response->getStatusCode(),
                Toxiproxy::OK,
                sprintf("Could not update upstream latency toxic for proxy '%s'", $proxy->getName())
            );
        });
    }
}