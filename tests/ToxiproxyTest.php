<?php

namespace Ihsw\ToxyproxyTests\Integration;

use Ihsw\Toxiproxy\Test\AbstractTest;
use Ihsw\Toxiproxy\Toxiproxy;

class ToxiproxyTest extends AbstractTest
{
    public function testConstructor()
    {
        $toxiproxy = new Toxiproxy(self::httpClientFactory());
        $this->assertTrue($toxiproxy instanceof Toxiproxy);
    }
}
