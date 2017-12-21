<?php

use GuzzleHttp\Client as HttpClient;
use Ihsw\Toxiproxy\Test\AbstractHttpTest;
use Ihsw\Toxiproxy\Toxiproxy;
use Ihsw\Toxiproxy\Proxy;

class LatencyTest extends AbstractHttpTest
{
    public function testUpdateDownstream()
    {
        $responses = [self::httpTestResponseFactory(Toxiproxy::OK, "set-latency-toxic.json")];
        $this->handleProxy($responses, function (Proxy $proxy) {
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
        $responses = [self::httpTestResponseFactory(Toxiproxy::OK, "set-latency-toxic.json")];
        $this->handleProxy($responses, function (Proxy $proxy) {
            $response = $proxy->updateUpstream("latency", ["latency" => 100, "jitter" => 100]);
            $this->assertEquals(
                $response->getStatusCode(),
                Toxiproxy::OK,
                sprintf("Could not update upstream latency toxic for proxy '%s'", $proxy->getName())
            );
        });
    }
}
