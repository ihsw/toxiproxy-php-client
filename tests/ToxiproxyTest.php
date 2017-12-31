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
        $toxiproxy = new Toxiproxy(self::TEST_BASE_URL);
        $this->assertTrue($toxiproxy instanceof Toxiproxy);
    }

    public function testGetHttpClient()
    {
        $toxiproxy = new Toxiproxy(self::TEST_BASE_URL);
        $this->assertTrue($toxiproxy->getHttpClient() instanceof HttpClient);
    }

    public function testCreate()
    {
        $toxiproxy = new Toxiproxy(self::TEST_BASE_URL);
        $proxy = $toxiproxy->create(self::TEST_NAME, self::TEST_UPSTREAM, self::TEST_LISTEN);
        $this->assertTrue($proxy instanceof Proxy);
    }
}
