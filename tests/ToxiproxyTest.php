<?php

namespace Ihsw\ToxyproxyTests\Integration;

use GuzzleHttp\Client as HttpClient;
use Ihsw\Toxiproxy\Proxy;
use Ihsw\Toxiproxy\Test\AbstractTest;
use Ihsw\Toxiproxy\Toxiproxy;

class ToxiproxyTest extends AbstractTest
{
    public function testConstructor()
    {
        $toxiproxy = $this->createToxiproxy();
        $this->assertTrue($toxiproxy instanceof Toxiproxy);
    }

    public function testGetHttpClient()
    {
        $toxiproxy = $this->createToxiproxy();
        $this->assertTrue($toxiproxy->getHttpClient() instanceof HttpClient);
    }

    public function testCreate()
    {
        $toxiproxy = $this->createToxiproxy();
        $proxy = $toxiproxy->create(self::TEST_NAME, self::TEST_UPSTREAM, self::TEST_LISTEN);
        $this->assertTrue($proxy instanceof Proxy);
    }
}
