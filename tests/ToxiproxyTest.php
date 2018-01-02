<?php

namespace Ihsw\ToxyproxyTests\Integration;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException as HttpClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Ihsw\Toxiproxy\Test\AbstractTest;
use Ihsw\Toxiproxy\Toxiproxy;
use Ihsw\Toxiproxy\Proxy;
use Ihsw\Toxiproxy\Exception\InvalidToxicException;
use Ihsw\Toxiproxy\Exception\UnexpectedStatusCodeException;
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
        $proxy = $toxiproxy->create(self::TEST_NAME, self::TEST_UPSTREAM, $this->getListen());
        $this->assertTrue($proxy instanceof Proxy);

        $this->assertEquals(self::TEST_NAME, $proxy->getName());
        $this->assertEquals(self::TEST_UPSTREAM, $proxy->getUpstream());
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

    /**
     * @doesNotPerformAssertions
     */
    public function testDelete()
    {
        $toxiproxy = $this->createToxiproxy();
        $toxiproxy->delete($this->createProxy($toxiproxy));
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

    public function testHandleHttpClientException()
    {
        $toxiproxy = $this->createToxiproxy();
        $table = [
            ["status" => Toxiproxy::CONFLICT, "expected" => ProxyExistsException::class],
            ["status" => Toxiproxy::NOT_FOUND, "expected" => NotFoundException::class],
            ["status" => Toxiproxy::BAD_REQUEST, "expected" => InvalidToxicException::class],
            ["status" => -1, "expected" => UnexpectedStatusCodeException::class]
        ];

        foreach ($table as $item) {
            $e = new HttpClientException("", new Request("POST", "/"), new Response($item["status"]));
            $this->assertInstanceOf($item["expected"], $toxiproxy->handleHttpClientException($e));
        }
    }
}
