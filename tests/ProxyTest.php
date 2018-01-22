<?php

namespace Ihsw\Toxiproxy\Test;
use Ihsw\Toxiproxy\Exception\ToxicExistsException;
use Ihsw\Toxiproxy\Exception\NotFoundException;
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

    public function testCreateDuplicate()
    {
        $toxiproxy = $this->createToxiproxy();
        $proxy = $this->createProxy($toxiproxy);
        $this->createToxic($proxy);
        try {

            $this->createToxic($proxy);
        } catch (\Exception $e) {
            $this->assertInstanceOf(ToxicExistsException::class, $e);
            $this->removeProxy($toxiproxy, $proxy);

            return;
        }

        $this->assertTrue(false);
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

    public function testGetNotFound()
    {
        $toxiproxy = $this->createToxiproxy();
        $proxy = $this->createProxy($toxiproxy);

        $toxic = $proxy->get("non-existent");
        $this->assertNull($toxic);

        $toxiproxy->delete($proxy);
    }

    public function testUpdate()
    {
        $toxiproxy = $this->createToxiproxy();
        $proxy = $this->createProxy($toxiproxy);
        $toxic = $this->createToxic($proxy);

        $toxic->setToxicity(0.5);
        $updatedToxic = $proxy->update($toxic);
        $this->assertEquals($updatedToxic->getToxicity(), $toxic->getToxicity());

        $toxiproxy->delete($proxy);
    }

    public function testDelete()
    {
        $toxiproxy = $this->createToxiproxy();
        $proxy = $this->createProxy($toxiproxy);
        $toxic = $this->createToxic($proxy);
        $proxy->delete($toxic);
        $toxiproxy->delete($proxy);
        $this->assertTrue(true);
    }

    public function testDeleteNotFound()
    {
        $toxiproxy = $this->createToxiproxy();
        $proxy = $this->createProxy($toxiproxy);
        $toxic = $this->createToxic($proxy);
        $proxy->delete($toxic);


        try {
            $proxy->delete($toxic);
        } catch (\Exception $e) {
            $this->assertInstanceOf(NotFoundException::class, $e);
            $toxiproxy->delete($proxy);

            return;
        }

        $this->assertTrue(false);
    }
}
