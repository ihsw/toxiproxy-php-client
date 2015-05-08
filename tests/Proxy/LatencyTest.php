<?php

use GuzzleHttp\Client as HttpClient;
use Ihsw\Toxiproxy\Test\AbstractHttpTest,
    Ihsw\Toxiproxy\Toxiproxy,
    Ihsw\Toxiproxy\Proxy;

class LatencyTest extends AbstractHttpTest
{
    public function testUpdateLatencyDownstream()
    {
        $responses = [self::httpTestResponseFactory(Toxiproxy::OK, "set-latency-toxic.json")];
        $this->handleProxy($responses, function(Proxy $proxy) {
            $response = $proxy->updateDownstream("latency", ["latency" => 100]);
            $this->assertEquals(
                $response->getStatusCode(),
                Toxiproxy::OK,
                sprintf("Could not update downstream latency toxic for proxy '%s'", $proxy["name"])
            );
        });
    }

    public function testUpdateLatencyUpstream()
    {
        $responses = [self::httpTestResponseFactory(Toxiproxy::OK, "set-latency-toxic.json")];
        $this->handleProxy($responses, function(Proxy $proxy) {
            $response = $proxy->updateUpstream("latency", ["latency" => 100]);
            $this->assertEquals(
                $response->getStatusCode(),
                Toxiproxy::OK,
                sprintf("Could not update upstream latency toxic for proxy '%s'", $proxy["name"])
            );
        });
    }

    public function testUpdateJitterDownstream()
    {
        $responses = [self::httpTestResponseFactory(Toxiproxy::OK, "set-latency-toxic.json")];
        $this->handleProxy($responses, function(Proxy $proxy) {
            $response = $proxy->updateDownstream("latency", ["jitter" => 100]);
            $this->assertEquals(
                $response->getStatusCode(),
                Toxiproxy::OK,
                sprintf("Could not update downstream latency toxic for proxy '%s'", $proxy["name"])
            );
        });
    }

    public function testUpdateJitterUpstream()
    {
        $responses = [self::httpTestResponseFactory(Toxiproxy::OK, "set-latency-toxic.json")];
        $this->handleProxy($responses, function(Proxy $proxy) {
            $response = $proxy->updateUpstream("latency", ["jitter" => 100]);
            $this->assertEquals(
                $response->getStatusCode(),
                Toxiproxy::OK,
                sprintf("Could not update upstream latency toxic for proxy '%s'", $proxy["name"])
            );
        });
    }
}