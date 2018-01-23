<?php

namespace Ihsw\Toxiproxy\Test;

use GuzzleHttp\Client as HttpClient;
use Ihsw\Toxiproxy\Toxiproxy;
use Ihsw\Toxiproxy\Proxy;
use Ihsw\Toxiproxy\ToxicTypes;
use Ihsw\Toxiproxy\Exception\ProxyExistsException;
use Ihsw\Toxiproxy\Exception\NotFoundException;
use Ihsw\Toxiproxy\Exception\InvalidProxyException;

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
        $listen = $this->getListen();
        $proxy = $toxiproxy->create(self::TEST_NAME, self::TEST_UPSTREAM_REDIS, $listen);
        $this->assertTrue($proxy instanceof Proxy);

        $this->assertEquals(self::TEST_NAME, $proxy->getName());
        $this->assertEquals(self::TEST_UPSTREAM_REDIS, $proxy->getUpstream());
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

    public function testPopulate()
    {
        $toxiproxy = $this->createToxiproxy();

        // producing a list of proposed proxy-bodies and the expected proxies
        $proxyBodies = [
            [
                "name" => self::TEST_NAME,
                "listen" => $this->getListen(),
                "upstream" => self::TEST_UPSTREAM_REDIS,
                "enabled" => true
            ]
        ];
        $expectedProxy = new Proxy($toxiproxy, $proxyBodies[0]["name"]);
        $expectedProxy->setEnabled($proxyBodies[0]["enabled"])
            ->setListen($proxyBodies[0]["listen"])
            ->setUpstream($proxyBodies[0]["upstream"]);
        $expectedProxies = [$expectedProxy];

        // populating
        $proxies = $toxiproxy->populate($proxyBodies);

        // comparing the results
        $this->assertEquals($expectedProxies, $proxies);

        // cleaning up
        $this->removeProxy($toxiproxy, $proxies[0]);
    }

    public function testPopulateBadData()
    {
        $toxiproxy = $this->createToxiproxy();

        try {
            $toxiproxy->populate(["bullshit" => "yes"]);
        } catch (\Exception $e) {
            $this->assertInstanceOf(InvalidProxyException::class, $e);

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

    public function testGetWithToxics()
    {
        $toxiproxy = $this->createToxiproxy();
        $proxy = $this->createProxy($toxiproxy);

        $toxics = [$this->createToxic($proxy, ToxicTypes::LATENCY, [
            "latency" => 1000,
            "jitter" => 500
        ])];
        $receivedProxy = $toxiproxy->get($proxy->getName());
        $this->assertEquals($toxics[0]->jsonSerialize(), $receivedProxy->getToxics()[0]->jsonSerialize());

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

        $proxy->setListen($this->getListen(43434))
            ->setUpstream(self::TEST_UPSTREAM_PSQL)
            ->setEnabled(false);
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
            $toxiproxy->update(new Proxy($toxiproxy, "not-found"));
        } catch (\Exception $e) {
            $this->assertInstanceOf(NotFoundException::class, $e);

            return;
        }

        $this->assertTrue(false);
    }

    public function testReset()
    {
        $toxiproxy = $this->createToxiproxy();

        // creating a proxy and a toxic, and disabling the proxy
        $proxy = $this->createProxy($toxiproxy);
        $this->createToxic($proxy, ToxicTypes::LATENCY, [
            "latency" => 1000,
            "jitter" => 500
        ]);
        $proxy->setEnabled(false);
        $toxiproxy->update($proxy);

        // resetting all proxies
        $toxiproxy->reset();

        // checking that this proxy is now re-enabled
        $proxy = $toxiproxy->get($proxy->getName());
        $this->assertTrue($proxy->isEnabled());

        // checking that this proxy has no toxics
        $toxics = $proxy->getAll();
        $this->assertEmpty($toxics);
    }

    public function testVersion()
    {
        $toxiproxy = $this->createToxiproxy();

        $this->assertNotEmpty($toxiproxy->version());
    }
}
