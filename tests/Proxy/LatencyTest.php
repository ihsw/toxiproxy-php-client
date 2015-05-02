<?php

use GuzzleHttp\Client as HttpClient;
use Ihsw\Toxiproxy\Test\AbstractTest,
    Ihsw\Toxiproxy\Toxiproxy,
    Ihsw\Toxiproxy\Proxy;

class LatencyTest extends AbstractTest
{
    public function testUpdateLatencyDownstream()
    {
        $this->handleProxy(function(Proxy $proxy){
            $response = $proxy->update("latency", "downstream", ["latency" => 100]);
            $this->assertEquals(
                $response->getStatusCode(),
                Toxiproxy::OK,
                sprintf("Could not update downstream latency toxic for proxy '%s'", $proxy["name"])
            );
        });
    }

    public function testUpdateLatencyUpstream()
    {
        $this->handleProxy(function(Proxy $proxy){
            $response = $proxy->update("latency", "upstream", ["latency" => 100]);
            $this->assertEquals(
                $response->getStatusCode(),
                Toxiproxy::OK,
                sprintf("Could not update upstream latency toxic for proxy '%s'", $proxy["name"])
            );
        });
    }

    public function testUpdateJitterDownstream()
    {
        $this->handleProxy(function(Proxy $proxy){
            $response = $proxy->update("latency", "downstream", ["jitter" => 100]);
            $this->assertEquals(
                $response->getStatusCode(),
                Toxiproxy::OK,
                sprintf("Could not update downstream latency toxic for proxy '%s'", $proxy["name"])
            );
        });
    }

    public function testUpdateJitterUpstream()
    {
        $this->handleProxy(function(Proxy $proxy){
            $response = $proxy->update("latency", "upstream", ["jitter" => 100]);
            $this->assertEquals(
                $response->getStatusCode(),
                Toxiproxy::OK,
                sprintf("Could not update upstream latency toxic for proxy '%s'", $proxy["name"])
            );
        });
    }
}