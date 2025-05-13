<?php

namespace Ihsw\Toxiproxy\Test;

use GuzzleHttp\Client as HttpClient;
use Ihsw\Toxiproxy\Exception\InvalidProxyException;
use Ihsw\Toxiproxy\Exception\NotFoundException;
use Ihsw\Toxiproxy\Exception\ProxyExistsException;
use Ihsw\Toxiproxy\Exception\UnexpectedStatusCodeException;
use Ihsw\Toxiproxy\Proxy;
use Ihsw\Toxiproxy\Test\Test\BaseTestCase;
use Ihsw\Toxiproxy\ToxicTypes;
use Ihsw\Toxiproxy\Toxiproxy;

class ToxiproxyTest extends BaseTestCase
{
    public function testConstructor(): void
    {
        $toxiproxy = $this->createToxiproxy();
        self::assertInstanceOf(Toxiproxy::class, $toxiproxy);
    }

    public function testGetHttpClient(): void
    {
        $toxiproxy = $this->createToxiproxy();
        self::assertInstanceOf(HttpClient::class, $toxiproxy->getHttpClient());
    }

    public function testCreate(): void
    {
        $toxiproxy = $this->createToxiproxy();
        $listen = $this->getListen();
        $proxy = $toxiproxy->create(self::PROXY_NAME, self::TEST_UPSTREAM, $listen);
        self::assertInstanceOf(Proxy::class, $proxy);

        self::assertEquals(self::PROXY_NAME, $proxy->getName());
        self::assertEquals(self::TEST_UPSTREAM, $proxy->getUpstream());
        self::assertEquals($listen, $proxy->getListen());
        self::assertTrue($proxy->isEnabled());

        $toxiproxy->delete($proxy);
    }

    public function testCreateDuplicate(): void
    {
        $toxiproxy = $this->createToxiproxy();
        $this->createProxy($toxiproxy);

        $this->expectException(ProxyExistsException::class);
        $this->createProxy($toxiproxy);
    }

    public function testCreateUnexpectedStatus(): void
    {
        $toxiproxy = $this->createToxiproxy([
            self::httpResponseFactory(418, ''),
        ]);

        $this->expectException(UnexpectedStatusCodeException::class);
        $this->createProxy($toxiproxy);
    }

    public function testPopulate(): void
    {
        $toxiproxy = $this->createToxiproxy();

        // producing a list of proposed proxy-bodies and the expected proxies
        $proxyBodies = [
            [
                'name' => self::PROXY_NAME,
                'listen' => $this->getListen(),
                'upstream' => self::TEST_UPSTREAM,
                'enabled' => true,
            ],
        ];
        $expectedProxy = new Proxy($toxiproxy, $proxyBodies[0]['name']);
        $expectedProxy->setEnabled($proxyBodies[0]['enabled'])
            ->setListen($proxyBodies[0]['listen'])
            ->setUpstream($proxyBodies[0]['upstream']);
        $expectedProxies = [$expectedProxy];

        // populating
        $proxies = $toxiproxy->populate($proxyBodies);

        // comparing the results
        self::assertEquals($expectedProxies, $proxies);
    }

    public function testPopulateBadData(): void
    {
        $toxiproxy = $this->createToxiproxy();

        $this->expectException(InvalidProxyException::class);
        $toxiproxy->populate(['bullshit' => 'yes']);
    }

    public function testPopulateUnexpectedStatus(): void
    {
        $toxiproxy = $this->createToxiproxy([
            self::httpResponseFactory(418, ''),
        ]);

        $this->expectException(UnexpectedStatusCodeException::class);
        $toxiproxy->populate([]);
    }

    public function testGet(): void
    {
        $toxiproxy = $this->createToxiproxy();
        $proxy = $this->createProxy($toxiproxy);

        self::assertEquals(
            $proxy->getName(),
            $toxiproxy->get($proxy->getName())->getName(),
        );
    }

    public function testGetUnexpectedStatus(): void
    {
        $toxiproxy = $this->createToxiproxy([
            self::httpResponseFactory(418, ''),
        ]);

        $this->expectException(UnexpectedStatusCodeException::class);
        $toxiproxy->get('');
    }

    public function testGetWithToxics(): void
    {
        $toxiproxy = $this->createToxiproxy();
        $proxy = $this->createProxy($toxiproxy);

        $toxics = [$this->createToxic($proxy, ToxicTypes::LATENCY->value, [
            'latency' => 1000,
            'jitter' => 500,
        ])];
        $receivedProxy = $toxiproxy->get($proxy->getName());
        self::assertSame(
            $toxics[0]->jsonSerialize(),
            $receivedProxy->getToxics()[0]->jsonSerialize(),
        );
    }

    public function testGetNotFound(): void
    {
        $toxiproxy = $this->createToxiproxy();
        $proxy = $toxiproxy->get('non-existent');
        self::assertNull($proxy);
    }

    public function testGetAll(): void
    {
        $toxiproxy = $this->createToxiproxy();
        $proxy = $this->createProxy($toxiproxy);
        $proxies = $toxiproxy->getAll();
        self::assertEquals($proxy, $proxies[0]);
    }

    public function testGetAllUnexpectedStatus(): void
    {
        $toxiproxy = $this->createToxiproxy([
            self::httpResponseFactory(418, ''),
        ]);

        $this->expectException(UnexpectedStatusCodeException::class);;;
        $toxiproxy->getAll();
    }

    public function testDelete(): void
    {
        $toxiproxy = $this->createToxiproxy();
        self::assertCount(0, $toxiproxy->getAll());;
        $toxiproxy->delete($this->createProxy($toxiproxy));
        self::assertCount(0, $toxiproxy->getAll());;
    }

    public function testDeleteNotFound(): void
    {
        $toxiproxy = $this->createToxiproxy();
        $proxy = $this->createProxy($toxiproxy);
        $toxiproxy->delete($proxy);

        $this->expectException(NotFoundException::class);;
        $toxiproxy->delete($proxy);
    }

    public function testDeleteUnexpectedStatus(): void
    {
        $toxiproxy = $this->createToxiproxy([
            self::httpResponseFactory(418, ''),
        ]);

        $this->expectException(UnexpectedStatusCodeException::class);
        $toxiproxy->delete(new Proxy($toxiproxy, ''));
    }

    public function testUpdate(): void
    {
        $toxiproxy = $this->createToxiproxy();
        $proxy = $this->createProxy($toxiproxy);

        $proxy->setListen($this->getListen(43434))
            ->setUpstream(self::TEST_UPSTREAM)
            ->setEnabled(false);
        $updatedProxy = $toxiproxy->update($proxy);
        self::assertEquals($proxy, $updatedProxy);
    }

    public function testUpdateDisabled(): void
    {
        $toxiproxy = $this->createToxiproxy();

        $proxy = $this->createProxy($toxiproxy);
        self::assertTrue($proxy->isEnabled());

        $proxy->setEnabled(false);
        $proxy = $toxiproxy->update($proxy);
        self::assertFalse($proxy->isEnabled());
    }

    public function testUpdateNotFound(): void
    {
        $toxiproxy = $this->createToxiproxy();

        $this->expectException(NotFoundException::class);
        $toxiproxy->update(new Proxy($toxiproxy, 'not-found'));
    }

    public function testUpdateUnexpectedStatus(): void
    {
        $toxiproxy = $this->createToxiproxy([
            self::httpResponseFactory(418, ''),
        ]);

        $this->expectException(UnexpectedStatusCodeException::class);
        $toxiproxy->update(new Proxy($toxiproxy, ''));
    }

    public function testReset(): void
    {
        $toxiproxy = $this->createToxiproxy();

        // creating a proxy and a toxic, and disabling the proxy
        $proxy = $this->createProxy($toxiproxy);
        $this->createToxic($proxy, ToxicTypes::LATENCY->value, [
            'latency' => 1000,
            'jitter' => 500,
        ]);
        $proxy->setEnabled(false);
        $toxiproxy->update($proxy);

        // resetting all proxies
        $toxiproxy->reset();

        // checking that this proxy is now re-enabled
        $proxy = $toxiproxy->get($proxy->getName());
        self::assertTrue($proxy->isEnabled());

        // checking that this proxy has no toxics
        $toxics = $proxy->getAll();
        self::assertEmpty($toxics);
    }

    public function testResetUnexpectedStatus(): void
    {
        $toxiproxy = $this->createToxiproxy([
            self::httpResponseFactory(418, ''),
        ]);

        $this->expectException(UnexpectedStatusCodeException::class);
        $toxiproxy->reset();
    }

    public function testVersion(): void
    {
        $toxiproxy = $this->createToxiproxy();

        self::assertNotEmpty($toxiproxy->version());
    }

    public function testVersionUnexpectedStatus(): void
    {
        $toxiproxy = $this->createToxiproxy([
            self::httpResponseFactory(418, ''),
        ]);

        $this->expectException(UnexpectedStatusCodeException::class);
        $toxiproxy->version();
    }
}
