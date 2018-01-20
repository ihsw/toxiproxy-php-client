<?php

namespace Ihsw\Toxiproxy\Test;

use GuzzleHttp\Client as HttpClient;
use Ihsw\Toxiproxy\Toxiproxy;
use Ihsw\Toxiproxy\Proxy;
use Ihsw\Toxiproxy\Exception\ProxyExistsException;
use Ihsw\Toxiproxy\Exception\NotFoundException;

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
        $proxy = $toxiproxy->create(self::TEST_NAME, self::TEST_UPSTREAM_REDIS, $this->getListen());
        $this->assertTrue($proxy instanceof Proxy);

        $this->assertEquals(self::TEST_NAME, $proxy->getName());
        $this->assertEquals(self::TEST_UPSTREAM_REDIS, $proxy->getUpstream());
        list($ip, $port) = explode(":", $this->getListen());
        $listen = sprintf("%s:%s", gethostbyname($ip), $port);
        $this->assertEquals($listen, $proxy->getListen());
        $this->assertTrue($proxy->isEnabled());
        $this->assertProxyAvailable($proxy);

        $toxiproxy->delete($proxy);
    }

    public function testCreateDuplicate()
    {
        $toxiproxy = $this->createToxiproxy();
        $proxy = $this->createProxy($toxiproxy);
        try {
            $this->createProxy($toxiproxy);
        } catch (\Exception $e) {
            $this->assertInstanceOf(ProxyExistsException::class, $e);
            $this->removeProxy($toxiproxy, $proxy);

            return;
        }

        $this->assertTrue(false);
    }

    public function testGet()
    {
        $toxiproxy = $this->createToxiproxy();
        $proxy = $this->createProxy($toxiproxy);
        $this->assertEquals($proxy->getName(), $toxiproxy->get($proxy->getName())->getName());
        $this->removeProxy($toxiproxy, $proxy);
    }

    public function testGetNotFound()
    {
        $toxiproxy = $this->createToxiproxy();
        $proxy = $toxiproxy->get("non-existent");
        $this->assertNull($proxy);
    }

    public function testGetAll()
    {
        $toxiproxy = $this->createToxiproxy();
        $proxy = $this->createProxy($toxiproxy);
        $proxies = $toxiproxy->getAll();
        $this->assertEquals($proxy, $proxies[0]);
        $this->removeProxy($toxiproxy, $proxy);
    }

    public function testDelete()
    {
        $toxiproxy = $this->createToxiproxy();
        $toxiproxy->delete($this->createProxy($toxiproxy));
        $this->assertTrue(true);
    }

    public function testDeleteNotFound()
    {
        $toxiproxy = $this->createToxiproxy();
        $proxy = $this->createProxy($toxiproxy);
        $toxiproxy->delete($proxy);

        try {
            $toxiproxy->delete($proxy);
        } catch (\Exception $e) {
            $this->assertInstanceOf(NotFoundException::class, $e);

            return;
        }

        $this->assertTrue(false);
    }

    public function testUpdate()
    {
        $toxiproxy = $this->createToxiproxy();
        $proxy = $this->createProxy($toxiproxy);

        $proxy->setUpstream(self::TEST_UPSTREAM_PSQL);
        $updatedProxy = $toxiproxy->update($proxy);
        $this->assertEquals($proxy, $updatedProxy);

        $toxiproxy->delete($updatedProxy);
    }

    public function testUpdateDisabled()
    {
        $toxiproxy = $this->createToxiproxy();

        $proxy = $this->createProxy($toxiproxy);
        $this->assertProxyAvailable($proxy);

        $proxy->setEnabled(false);
        $proxy = $toxiproxy->update($proxy);
        $this->assertProxyUnavailable($proxy);

        $toxiproxy->delete($proxy);
    }

    public function testUpdateNotFound()
    {
        $toxiproxy = $this->createToxiproxy();

        try {
            $toxiproxy->update(new Proxy($toxiproxy));
        } catch (\Exception $e) {
            $this->assertInstanceOf(NotFoundException::class, $e);

            return;
        }

        $this->assertTrue(false);
    }
}
