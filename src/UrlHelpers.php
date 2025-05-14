<?php

declare(strict_types=1);

namespace Ihsw\Toxiproxy;

trait UrlHelpers
{

    /**
     * @return array{method: string, uri: string}
     */
    public function getProxiesRoute(): array
    {
        return ['method' => 'GET', 'uri' => '/proxies'];
    }

    /**
     * @return array{method: string, uri: string}
     */
    public function createProxyRoute(): array
    {
        return ['method' => 'POST', 'uri' => '/proxies'];
    }

    /**
     * @return array{method: string, uri: string}
     */
    public function populateRoute(): array
    {
        return ['method' => 'POST', 'uri' => '/populate'];
    }

    /**
     * @return array{method: string, uri: string}
     */
    public function getProxyRoute(string $name): array
    {
        return ['method' => 'GET', 'uri' => sprintf('/proxies/%s', $name)];
    }

    /**
     * @return array{method: string, uri: string}
     */
    public function updateProxyRoute(Proxy $proxy): array
    {
        return ['method' => 'POST', 'uri' => sprintf('/proxies/%s', $proxy->getName())];
    }

    /**
     * @return array{method: string, uri: string}
     */
    public function deleteProxyRoute(Proxy $proxy): array
    {
        return ['method' => 'DELETE', 'uri' => sprintf('/proxies/%s', $proxy->getName())];
    }

    /**
     * @return array{method: string, uri: string}
     */
    public function getToxicsRoute(Proxy $proxy): array
    {
        return ['method' => 'GET', 'uri' => sprintf('/proxies/%s/toxics', $proxy->getName())];
    }

    /**
     * @return array{method: string, uri: string}
     */
    public function createToxicRoute(Proxy $proxy): array
    {
        return ['method' => 'POST', 'uri' => sprintf('/proxies/%s/toxics', $proxy->getName())];
    }

    /**
     * @return array{method: string, uri: string}
     */
    public function getToxicRoute(Proxy $proxy, string $name): array
    {
        return ['method' => 'GET', 'uri' => sprintf('/proxies/%s/toxics/%s', $proxy->getName(), $name)];
    }

    /**
     * @return array{method: string, uri: string}
     */
    public function updateToxicRoute(Proxy $proxy, Toxic $toxic): array
    {
        return ['method' => 'POST', 'uri' => sprintf('/proxies/%s/toxics/%s', $proxy->getName(), $toxic->getName())];
    }

    /**
     * @return array{method: string, uri: string}
     */
    public function deleteToxicRoute(Proxy $proxy, Toxic $toxic): array
    {
        return ['method' => 'DELETE', 'uri' => sprintf('/proxies/%s/toxics/%s', $proxy->getName(), $toxic->getName())];
    }

    /**
     * @return array{method: string, uri: string}
     */
    public function resetRoute(): array
    {
        return ['method' => 'POST', 'uri' => '/reset'];
    }

    /**
     * @return array{method: string, uri: string}
     */
    public function versionRoute(): array
    {
        return ['method' => 'GET', 'uri' => '/version'];
    }
}
