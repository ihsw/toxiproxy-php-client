<?php

namespace Ihsw\Toxiproxy\Test;

use PHPUnit\Framework\TestCase;
use Ihsw\Toxiproxy\Toxiproxy;
use Ihsw\Toxiproxy\Proxy;
use Ihsw\Toxiproxy\Toxic;
use Ihsw\Toxiproxy\StreamDirections;
use Ihsw\Toxiproxy\ToxicTypes;

abstract class AbstractTest extends TestCase
{
    const TEST_NAME = "ihsw_test_redis_master";
    const TEST_UPSTREAM_REDIS = "127.0.0.1:6379";
    const TEST_UPSTREAM_PSQL = "127.0.0.1:5432";

    use AssertionHelpers;

    /**
     * @return Toxiproxy
     */
    protected function createToxiproxy()
    {
        return new Toxiproxy(sprintf("http://%s:8474", $this->getToxiproxyHost()));
    }

    /**
     * @return string
     */
    private function getToxiproxyHost()
    {
        return getenv("TOXIPROXY_HOST");
    }

    /**
     * @param int $listen
     * @return string
     */
    protected function getListen($listen = 34343)
    {
        return sprintf("%s:%s", $this->getToxiproxyHost(), $listen);
    }

    /**
     * @param Toxiproxy $toxiproxy
     * @return Proxy|null
     */
    protected function createProxy(Toxiproxy $toxiproxy)
    {
        return $toxiproxy->create(self::TEST_NAME, self::TEST_UPSTREAM_REDIS, $this->getListen());
    }

    /**
     * @param Toxiproxy $toxiproxy
     * @param Proxy $proxy
     */
    protected function removeProxy(Toxiproxy $toxiproxy, Proxy $proxy)
    {
        $toxiproxy->delete($proxy);
    }

    /**
     * @param Proxy $proxy
     * @param string $type
     * @param array $attr
     * @return Toxic
     */
    protected function createToxic(Proxy $proxy, $type, array $attr)
    {
        return $proxy->create($type, StreamDirections::UPSTREAM, 1.0, $attr);
    }

    protected function removeToxic(Proxy $proxy, Toxic $toxic)
    {
        $proxy->delete($toxic);
    }
}
