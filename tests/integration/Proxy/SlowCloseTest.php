<?php

use GuzzleHttp\Client as HttpClient;
use Ihsw\Toxiproxy\Test\AbstractTest,
    Ihsw\Toxiproxy\Toxiproxy,
    Ihsw\Toxiproxy\Proxy;

class SlowCloseTest extends AbstractTest
{
    public function testUpdateDownstream()
    {
        $this->handleProxy(function(Proxy $proxy) {
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
        $this->handleProxy(function(Proxy $proxy) {
            $response = $proxy->updateUpstream("slow_close", ["delay" => 1000]);
            $this->assertEquals(
                $response->getStatusCode(),
                Toxiproxy::OK,
                sprintf("Could not update upstream slow_close toxic for proxy '%s'", $proxy->getName())
            );
        });
    }
}