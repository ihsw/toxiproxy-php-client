<?php

use GuzzleHttp\Client as HttpClient;
use Ihsw\Toxiproxy\Test\AbstractTest,
    Ihsw\Toxiproxy\Toxiproxy,
    Ihsw\Toxiproxy\Proxy;

class JitterTest extends AbstractTest
{
    public function testUpdateSlowCloseDownstream()
    {
        $this->handleProxy(function(Proxy $proxy){
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
        $this->handleProxy(function(Proxy $proxy){
            $response = $proxy->updateUpstream("slow_close", ["delay" => 1000]);
            $this->assertEquals(
                $response->getStatusCode(),
                Toxiproxy::OK,
                sprintf("Could not update upstream slow_close toxic for proxy '%s'", $proxy["name"])
            );
        });
    }
}