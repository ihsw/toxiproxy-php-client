<?php

namespace Ihsw\Toxiproxy\Test;

class ProxyTest extends AbstractTest
{
    public function testCreate()
    {
        $toxiproxy = $this->createToxiproxy();
        $proxy = $this->createProxy($toxiproxy);

        $attr = [
            "latency" => 1000,
            "jitter" => 50
        ];
        $toxic = $proxy->create("latency", "upstream", 1.0, $attr);
        $this->assertEquals("latency", $toxic->getType());
        $this->assertEquals("upstream", $toxic->getStream());
        $this->assertEquals(1.0, $toxic->getToxicity());
        $this->assertEquals($attr, $toxic->getAttributes());

        $toxiproxy->delete($proxy);
    }
}
