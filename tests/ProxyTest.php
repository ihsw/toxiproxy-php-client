<?php

namespace Ihsw\Toxiproxy\Test;

use Ihsw\Toxiproxy\Proxy;
use Ihsw\Toxiproxy\Toxic;
use Ihsw\Toxiproxy\ToxicTypes;
use Ihsw\Toxiproxy\StreamDirections;
use Ihsw\Toxiproxy\StatusCodes;
use Ihsw\Toxiproxy\Exception\ToxicExistsException;
use Ihsw\Toxiproxy\Exception\NotFoundException;
use Ihsw\Toxiproxy\Exception\UnexpectedStatusCodeException;

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
        $toxic = $this->createToxic($proxy, ToxicTypes::LATENCY, [
            "latency" => 1000,
            "jitter" => 500
        ]);

        try {
            $this->createToxic($proxy, ToxicTypes::LATENCY, [
                "latency" => 1000,
                "jitter" => 500
            ]);
        } catch (\Exception $e) {
            $this->assertInstanceOf(ToxicExistsException::class, $e);
            $this->removeToxic($proxy, $toxic);
            $this->removeProxy($toxiproxy, $proxy);

            return;
        }

        $this->assertTrue(false);
    }

    public function testCreatedUnexpectedStatus()
    {
        $toxiproxy = $this->createToxiproxy([
            self::httpResponseFactory(StatusCodes::INVALID, "")
        ]);

        try {
            $this->createToxic(new Proxy($toxiproxy, ""), "", []);
        } catch (\Exception $e) {
            $this->assertInstanceOf(UnexpectedStatusCodeException::class, $e);

            return;
        }

        $this->assertTrue(false);
    }

    public function testGetAll()
    {
        $toxiproxy = $this->createToxiproxy();
        $proxy = $this->createProxy($toxiproxy);
        $toxic = $this->createToxic($proxy, ToxicTypes::LATENCY, [
            "latency" => 1000,
            "jitter" => 500
        ]);

        $toxics = $proxy->getAll();
        $this->assertEquals($toxic, $toxics[0]);

        $this->removeToxic($proxy, $toxic);
        $this->removeProxy($toxiproxy, $proxy);
    }

    public function testGetAllUnexpectedStatus()
    {
        $toxiproxy = $this->createToxiproxy([
            self::httpResponseFactory(StatusCodes::INVALID, "")
        ]);

        try {
            $proxy = new Proxy($toxiproxy, "");
            $proxy->getAll();
        } catch (\Exception $e) {
            $this->assertInstanceOf(UnexpectedStatusCodeException::class, $e);

            return;
        }

        $this->assertTrue(false);
    }

    public function testGet()
    {
        $toxiproxy = $this->createToxiproxy();
        $proxy = $this->createProxy($toxiproxy);
        $toxic = $this->createToxic($proxy, ToxicTypes::LATENCY, [
            "latency" => 1000,
            "jitter" => 500
        ]);

        $this->assertEquals($toxic, $proxy->get($toxic->getName()));

        $this->removeToxic($proxy, $toxic);
        $this->removeProxy($toxiproxy, $proxy);
    }

    public function testGetNotFound()
    {
        $toxiproxy = $this->createToxiproxy();
        $proxy = $this->createProxy($toxiproxy);

        $toxic = $proxy->get("non-existent");
        $this->assertNull($toxic);

        $this->removeProxy($toxiproxy, $proxy);
    }

    public function testGetUnexpectedStatus()
    {
        $toxiproxy = $this->createToxiproxy([
            self::httpResponseFactory(StatusCodes::INVALID, "")
        ]);

        try {
            $proxy = new Proxy($toxiproxy, "");
            $proxy->get("");
        } catch (\Exception $e) {
            $this->assertInstanceOf(UnexpectedStatusCodeException::class, $e);

            return;
        }

        $this->assertTrue(false);
    }

    public function testUpdate()
    {
        $toxiproxy = $this->createToxiproxy();
        $proxy = $this->createProxy($toxiproxy);
        $toxic = $this->createToxic($proxy, ToxicTypes::LATENCY, [
            "latency" => 1000,
            "jitter" => 500
        ]);

        $toxic->setAttributes(["latency" => 2000, "jitter" => 1000])
            ->setToxicity(0.5);
        $updatedToxic = $proxy->update($toxic);
        $this->assertEquals($updatedToxic, $toxic);

        $this->removeToxic($proxy, $toxic);
        $this->removeProxy($toxiproxy, $proxy);
    }

    public function testUpdateNotFound()
    {
        $toxiproxy = $this->createToxiproxy();
        $proxy = $this->createProxy($toxiproxy);
        $toxic = $this->createToxic($proxy, ToxicTypes::LATENCY, [
            "latency" => 1000,
            "jitter" => 500
        ]);

        $proxy->delete($toxic);
        try {
            $proxy->update($toxic);
        } catch (\Exception $e) {
            $this->assertInstanceOf(NotFoundException::class, $e);
            $this->removeProxy($toxiproxy, $proxy);

            return;
        }

        $this->assertTrue(false);
    }

    public function testUpdateUnexpectedStatus()
    {
        $toxiproxy = $this->createToxiproxy([
            self::httpResponseFactory(StatusCodes::INVALID, "")
        ]);

        try {
            $proxy = new Proxy($toxiproxy, "");
            $proxy->update(new Toxic($proxy, "", "", ""));
        } catch (\Exception $e) {
            $this->assertInstanceOf(UnexpectedStatusCodeException::class, $e);

            return;
        }

        $this->assertTrue(false);
    }

    public function testDelete()
    {
        $toxiproxy = $this->createToxiproxy();
        $proxy = $this->createProxy($toxiproxy);
        $toxic = $this->createToxic($proxy, ToxicTypes::LATENCY, [
            "latency" => 1000,
            "jitter" => 500
        ]);

        $proxy->delete($toxic);
        $this->assertTrue(true);

        $this->removeProxy($toxiproxy, $proxy);
    }

    public function testDeleteNotFound()
    {
        $toxiproxy = $this->createToxiproxy();
        $proxy = $this->createProxy($toxiproxy);
        $toxic = $this->createToxic($proxy, ToxicTypes::LATENCY, [
            "latency" => 1000,
            "jitter" => 500
        ]);

        $proxy->delete($toxic);
        try {
            $proxy->delete($toxic);
        } catch (\Exception $e) {
            $this->assertInstanceOf(NotFoundException::class, $e);
            $this->removeProxy($toxiproxy, $proxy);

            return;
        }

        $this->assertTrue(false);
    }

    public function testDeleteUnexpectedStatus()
    {
        $toxiproxy = $this->createToxiproxy([
            self::httpResponseFactory(StatusCodes::INVALID, "")
        ]);

        try {
            $proxy = new Proxy($toxiproxy, "");
            $proxy->delete(new Toxic($proxy, "", "", ""));
        } catch (\Exception $e) {
            $this->assertInstanceOf(UnexpectedStatusCodeException::class, $e);

            return;
        }

        $this->assertTrue(false);
    }
}
