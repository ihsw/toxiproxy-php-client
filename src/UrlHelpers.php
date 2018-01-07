<?php

namespace Ihsw\Toxiproxy;

trait UrlHelpers
{
    /**
     * @return array
     */
    public function getProxiesRoute()
    {
        return ["method" => "GET", "uri" => "/proxies"];
    }

    /**
     * @return array
     */
    public function createProxyRoute()
    {
        return ["method" => "POST", "uri" => "/proxies"];
    }

    /**
     * @return array
     */
    public function populateRoute()
    {
        return ["method" => "POST", "uri" => "/populate"];
    }

    /**
     * @param string
     * @return array
     */
    public function getProxyRoute($name)
    {
        return ["method" => "GET", "uri" => sprintf("/proxies/%s", $name)];
    }

    /**
     * @param Proxy $proxy
     * @return array
     */
    public function updateProxyRoute(Proxy $proxy)
    {
        return ["method" => "POST", "uri" => sprintf("/proxies/%s", $proxy->getName())];
    }

    /**
     * @param Proxy $proxy
     * @return array
     */
    public function deleteProxyRoute(Proxy $proxy)
    {
        return ["method" => "DELETE", "uri" => sprintf("/proxies/%s", $proxy->getName())];
    }

    /**
     * @param Proxy $proxy
     * @return array
     */
    public function getToxicsRoute(Proxy $proxy)
    {
        return ["method" => "GET", "uri" => sprintf("/proxies/%s/toxics", $proxy->getName())];
    }

    /**
     * @param Proxy $proxy
     * @return array
     */
    public function createToxicRoute(Proxy $proxy)
    {
        return ["method" => "POST", "uri" => sprintf("/proxies/%s/toxics", $proxy->getName())];
    }

    /**
     * @param Proxy $proxy
     * @param Toxic $toxic
     * @return array
     */
    public function getToxicRoute(Proxy $proxy, Toxic $toxic)
    {
        return [
            "method" => "GET",
            "uri" => sprintf("/proxies/%s/toxics/%s", $proxy->getName(), $toxic->getName())
        ];
    }

    /**
     * @param Proxy $proxy
     * @param Toxic $toxic
     * @return array
     */
    public function updateToxicRoute(Proxy $proxy, Toxic $toxic)
    {
        return [
            "method" => "POST",
            "uri" => sprintf("/proxies/%s/toxics/%s", $proxy->getName(), $toxic->getName())
        ];
    }

    /**
     * @param Proxy $proxy
     * @param Toxic $toxic
     * @return array
     */
    public function deleteToxicRoute(Proxy $proxy, Toxic $toxic)
    {
        return [
            "method" => "DELETE",
            "uri" => sprintf("/proxies/%s/toxics/%s", $proxy->getName(), $toxic->getName())
        ];
    }

    /**
     * @return array
     */
    public function resetRoute()
    {
        return ["method" => "POST", "uri" => "/reset"];
    }

    /**
     * @return array
     */
    public function versionRoute()
    {
        return ["method" => "GET", "uri" => "/version"];
    }
}
