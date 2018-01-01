<?php

namespace Ihsw\Toxiproxy\Test;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Ihsw\Toxiproxy\Toxiproxy;
use Ihsw\Toxiproxy\Proxy;

abstract class AbstractTest extends TestCase
{
    const TEST_NAME = "ihsw_test_redis_master";
    const TEST_UPSTREAM = "127.0.0.1:6379";
    const TEST_LISTEN = "127.0.0.1:34343";

    use AssertionHelpers;

    protected function createToxiproxy()
    {
        return new Toxiproxy(getenv("TOXIPROXY_URL"));
    }

    /**
     * @param Toxiproxy $toxiproxy
     * @return Proxy|null
     */
    protected function createProxy(Toxiproxy $toxiproxy)
    {
        $toxiproxy = $this->createToxiproxy();
        return $toxiproxy->create(self::TEST_NAME, self::TEST_UPSTREAM, self::TEST_LISTEN);
    }

    /**
     * @param Toxiproxy $toxiproxy
     * @param Proxy $proxy
     */
    protected function removeProxy(Toxiproxy $toxiproxy, Proxy $proxy)
    {
        $toxiproxy->delete($proxy);
    }
}
