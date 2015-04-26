<?php

use Ihsw\Toxiproxy\Toxiproxy;

class ToxiproxyTest extends \PHPUnit_Framework_TestCase
{
    public function testHasDerp()
    {
        $toxiproxy = new Toxiproxy();
        $this->assertTrue($toxiproxy->hasDerp(true), "Has derp");
    }
}