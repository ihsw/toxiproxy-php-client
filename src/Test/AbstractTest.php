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

    protected function getListen()
    {
        return sprintf("%s:34343", $this->getToxiproxyHost());
    }

    /**
     * @param Toxiproxy $toxiproxy
     * @return Proxy|null
     */
    protected function createProxy(Toxiproxy $toxiproxy)
    {
        $toxiproxy = $this->createToxiproxy();
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
     * @return Toxic
     */
    protected function createToxic(Proxy  $proxy)
    {
        $attr = [
            "latency" => 1000,
            "jitter" => 50
        ];
        return $proxy->create(ToxicTypes::LATENCY, StreamDirections::UPSTREAM, 1.0, $attr);
    }

    protected function removeToxic(Proxy $proxy, Toxic $toxic)
    {
        $proxy->delete($toxic);
    }
}
