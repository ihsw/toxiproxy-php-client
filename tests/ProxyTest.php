<?php

namespace Ihsw\Toxiproxy\Test;
use Ihsw\Toxiproxy\ToxicTypes;
use Ihsw\Toxiproxy\StreamDirections;

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
        $toxic = $proxy->create(ToxicTypes::LATENCY, StreamDirections::UPSTREAM, 1.0, $attr);
        $this->assertEquals(ToxicTypes::LATENCY, $toxic->getType());
        $this->assertEquals(StreamDirections::UPSTREAM, $toxic->getStream());
        $this->assertEquals(1.0, $toxic->getToxicity());
        $this->assertEquals($attr, $toxic->getAttributes());

        $toxiproxy->delete($proxy);
    }

    public function testGetAll()
    {
        $toxiproxy = $this->createToxiproxy();
        $proxy = $this->createProxy($toxiproxy);
        $toxic = $this->createToxic($proxy);

        $toxics = $proxy->getAll();
        $this->assertEquals($toxic, $toxics[0]);

        $toxiproxy->delete($proxy);
    }

    public function testGet()
    {
        $toxiproxy = $this->createToxiproxy();
        $proxy = $this->createProxy($toxiproxy);
        $toxic = $this->createToxic($proxy);

        $this->assertEquals($toxic, $proxy->get($toxic->getName()));

        $toxiproxy->delete($proxy);
    }

    public function testUpdate()
    {
        $toxiproxy = $this->createToxiproxy();
        $proxy = $this->createProxy($toxiproxy);
        $toxic = $this->createToxic($proxy);

        $toxic->setStream(StreamDirections::DOWNSTREAM);
        $updatedToxic = $proxy->update($toxic);
//        $this->assertEquals($updatedToxic->getStream(), $toxic->getStream());

        $toxiproxy->delete($proxy);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testDelete()
    {
        $toxiproxy = $this->createToxiproxy();
        $proxy = $this->createProxy($toxiproxy);
        $toxic = $this->createToxic($proxy);
        $proxy->delete($toxic);
        $toxiproxy->delete($proxy);
    }
}
