<?php

namespace Ihsw\Toxiproxy\Test\Test;

use Ihsw\Toxiproxy\Proxy;
use Ihsw\Toxiproxy\StreamDirections;
use Ihsw\Toxiproxy\Toxic;
use Ihsw\Toxiproxy\Toxiproxy;
use PHPUnit\Framework\TestCase;

abstract class BaseTestCase extends TestCase
{
    protected const string PROXY_NAME = 'proxy_name';
    protected const string TEST_UPSTREAM = '127.0.0.1:6379';

    use HttpMockHelpers;

    public function setUp(): void
    {
        parent::setUp();
        $toxiproxy = $this->createToxiproxy();
        foreach ($toxiproxy->getAll() as $proxy) {
            $toxiproxy->delete($proxy);
        }
    }

    protected function createToxiproxy(?array $mockResponses = null): Toxiproxy
    {
        if ($mockResponses === null) {
            return new Toxiproxy(sprintf('http://%s:8474', $this->getToxiproxyHost()));
        }

        $toxiproxy = new Toxiproxy('');
        $toxiproxy->setHttpClient(self::mockHttpClientFactory($mockResponses));
        return $toxiproxy;
    }

    private function getToxiproxyHost(): string
    {
        return (string) getenv('TOXIPROXY_HOST');
    }

    protected function getListen(int $listen = 34343): string
    {
        return sprintf('%s:%s', '127.0.0.1', $listen);
    }

    protected function createProxy(Toxiproxy $toxiproxy): ?Proxy
    {
        return $toxiproxy->create(self::PROXY_NAME, self::TEST_UPSTREAM, $this->getListen());
    }


    protected function createToxic(Proxy $proxy, string $type, array $attr): Toxic
    {
        return $proxy->create($type, StreamDirections::UPSTREAM->value, 1.0, $attr);
    }
}
