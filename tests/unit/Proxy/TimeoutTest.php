<?php

use GuzzleHttp\Client as HttpClient;
use Ihsw\Toxiproxy\Test\AbstractHttpTest;
use Ihsw\Toxiproxy\Toxiproxy;
use Ihsw\Toxiproxy\Proxy;

class TimeoutTest extends AbstractHttpTest
{
    public function testUpdateDownstream()
    {
        $responses = [self::httpTestResponseFactory(Toxiproxy::OK, "set-timeout-toxic.json")];
        $this->handleProxy($responses, function (Proxy $proxy) {
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
        $responses = [self::httpTestResponseFactory(Toxiproxy::OK, "set-timeout-toxic.json")];
        $this->handleProxy($responses, function (Proxy $proxy) {
            $response = $proxy->updateUpstream("timeout", ["timeout" => 1000]);
            $this->assertEquals(
                $response->getStatusCode(),
                Toxiproxy::OK,
                sprintf("Could not update upstream timeout toxic for proxy '%s'", $proxy->getName())
            );
        });
    }
}
