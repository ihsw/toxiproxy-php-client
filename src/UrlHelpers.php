<?php

namespace Ihsw\Toxiproxy;

trait UrlHelpers
{
    public function getProxiesRoute(): array
    {
        return ['method' => 'GET', 'uri' => '/proxies'];
    }

    public function createProxyRoute(): array
    {
        return ['method' => 'POST', 'uri' => '/proxies'];
    }

    public function populateRoute(): array
    {
        return ['method' => 'POST', 'uri' => '/populate'];
    }

    public function getProxyRoute(string $name): array
    {
        return ['method' => 'GET', 'uri' => sprintf('/proxies/%s', $name)];
    }

    public function updateProxyRoute(Proxy $proxy): array
    {
        return ['method' => 'POST', 'uri' => sprintf('/proxies/%s', $proxy->getName())];
    }

    
    public function deleteProxyRoute(Proxy $proxy): array
    {
        return ['method' => 'DELETE', 'uri' => sprintf('/proxies/%s', $proxy->getName())];
    }


    public function getToxicsRoute(Proxy $proxy): array
    {
        return ['method' => 'GET', 'uri' => sprintf('/proxies/%s/toxics', $proxy->getName())];
    }

    public function createToxicRoute(Proxy $proxy): array
    {
        return ['method' => 'POST', 'uri' => sprintf('/proxies/%s/toxics', $proxy->getName())];
    }

    public function getToxicRoute(Proxy $proxy, string $name): array
    {
        return ['method' => 'GET', 'uri' => sprintf('/proxies/%s/toxics/%s', $proxy->getName(), $name)];
    }

    public function updateToxicRoute(Proxy $proxy, Toxic $toxic): array
    {
        return ['method' => 'POST', 'uri' => sprintf('/proxies/%s/toxics/%s', $proxy->getName(), $toxic->getName())];
    }

    public function deleteToxicRoute(Proxy $proxy, Toxic $toxic): array
    {
        return ['method' => 'DELETE', 'uri' => sprintf('/proxies/%s/toxics/%s', $proxy->getName(), $toxic->getName())];
    }
    
    public function resetRoute(): array
    {
        return ['method' => 'POST', 'uri' => '/reset'];
    }

    public function versionRoute(): array
    {
        return ['method' => 'GET', 'uri' => '/version'];
    }
}
