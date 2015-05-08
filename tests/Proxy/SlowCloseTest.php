<?php

use GuzzleHttp\Client as HttpClient;
use Ihsw\Toxiproxy\Test\AbstractHttpTest,
    Ihsw\Toxiproxy\Toxiproxy,
    Ihsw\Toxiproxy\Proxy;

class JitterTest extends AbstractHttpTest
{
    public function testUpdateSlowCloseDownstream()
    {
        $responses = [self::httpTestResponseFactory(Toxiproxy::OK, "set-latency-toxic.json")];
        $this->handleProxy($responses, function(Proxy $proxy) {
            $response = $proxy->updateDownstream("slow_close", ["delay" => 1000]);
            $this->assertEquals(
                $response->getStatusCode(),
                Toxiproxy::OK,
                sprintf("Could not update downstream slow_close toxic for proxy '%s'", $proxy["name"])
            );
        });
    }

    public function testUpdateSlowCloseUpstream()
    {
        $responses = [self::httpTestResponseFactory(Toxiproxy::OK, "set-latency-toxic.json")];
        $this->handleProxy($responses, function(Proxy $proxy) {
            $response = $proxy->updateUpstream("slow_close", ["delay" => 1000]);
            $this->assertEquals(
                $response->getStatusCode(),
                Toxiproxy::OK,
                sprintf("Could not update upstream slow_close toxic for proxy '%s'", $proxy["name"])
            );
        });
    }
}