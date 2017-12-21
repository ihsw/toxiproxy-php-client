<?php

use GuzzleHttp\Client as HttpClient;
use Ihsw\Toxiproxy\Test\AbstractHttpTest;
use Ihsw\Toxiproxy\Toxiproxy;
use Ihsw\Toxiproxy\Proxy;

class SlowCloseTest extends AbstractHttpTest
{
    public function testUpdateDownstream()
    {
        $responses = [self::httpTestResponseFactory(Toxiproxy::OK, "set-slow-close-toxic.json")];
        $this->handleProxy($responses, function (Proxy $proxy) {
            $response = $proxy->updateDownstream("slow_close", ["delay" => 1000]);
            $this->assertEquals(
                $response->getStatusCode(),
                Toxiproxy::OK,
                sprintf("Could not update downstream slow_close toxic for proxy '%s'", $proxy->getName())
            );
        });
    }

    public function testUpdateUpstream()
    {
        $responses = [self::httpTestResponseFactory(Toxiproxy::OK, "set-slow-close-toxic.json")];
        $this->handleProxy($responses, function (Proxy $proxy) {
            $response = $proxy->updateUpstream("slow_close", ["delay" => 1000]);
            $this->assertEquals(
                $response->getStatusCode(),
                Toxiproxy::OK,
                sprintf("Could not update upstream slow_close toxic for proxy '%s'", $proxy->getName())
            );
        });
    }
}
