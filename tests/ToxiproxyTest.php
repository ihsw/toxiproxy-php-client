<?php

use Ihsw\Toxiproxy\Client as ToxiproxyClient;

class ToxiproxyClientTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $client = new ToxiproxyClient("blah", "blah");
        $this->assertTrue(!is_null($client->getResponse()), "Client response was null");
    }
}