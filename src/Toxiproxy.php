<?php

namespace Ihsw\Toxiproxy;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException as HttpClientException;
use Psr\Http\Message\ResponseInterface;
use Ihsw\Toxiproxy\Exception\ProxyExistsException;
use Ihsw\Toxiproxy\Exception\InvalidProxyException;
use Ihsw\Toxiproxy\Exception\NotFoundException;
use Ihsw\Toxiproxy\Exception\UnexpectedStatusCodeException;

class Toxiproxy
{
    use UrlHelpers;

    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * Toxiproxy constructor.
     * @param string $baseUrl
     */
    public function __construct($baseUrl)
    {
        $this->httpClient = new HttpClient(["base_uri" => $baseUrl]);
    }

    /**
     * @return HttpClient
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }

    /**
     * @param ResponseInterface $response
     * @return Proxy
     */
    private function responseToProxy(ResponseInterface $response)
    {
        return $this->contentsToProxy(json_decode($response->getBody(), true));
    }

    /**
     * @param array $contents
     * @return Proxy
     */
    private function contentsToProxy(array $contents)
    {
        $proxy = new Proxy($this, $contents["name"], $contents["toxics"]);
        $proxy->setEnabled($contents["enabled"])
            ->setUpstream($contents["upstream"])
            ->setListen($contents["listen"]);

        return $proxy;
    }

    /**
     * @param string $name
     * @param string $upstream
     * @param string|null $listen
     * @return Proxy
     * @throws ProxyExistsException|UnexpectedStatusCodeException
     */
    public function create($name, $upstream, $listen = null)
    {
        try {
            $route = $this->createProxyRoute();
            return $this->responseToProxy(
                $this->httpClient->request($route["method"], $route["uri"], [
                    "body" => json_encode(["name" => $name, "upstream" => $upstream, "listen" => $listen])
                ])
            );
        } catch (HttpClientException $e) {
            switch ($e->getResponse()->getStatusCode()) {
                case StatusCodes::CONFLICT:
                    throw new ProxyExistsException(
                        $e->getResponse()->getBody(),
                        $e->getCode(),
                        $e
                    );
                default:
                    throw new UnexpectedStatusCodeException(
                        $e->getResponse()->getBody(),
                        $e->getCode(),
                        $e
                    );
            }
        }
    }

    /**
     * @param array $proxyBodies
     * @return Proxy[]
     * @throws InvalidProxyException|UnexpectedStatusCodeException
     */
    public function populate(array $proxyBodies)
    {
        $route = $this->populateRoute();
        try {
            $response = $this->httpClient->request($route["method"], $route["uri"], [
                "body" => json_encode($proxyBodies)
            ]);
            $contents = json_decode($response->getBody(), true);
            return array_map(function ($contents) {
                return $this->contentsToProxy($contents);
            }, $contents["proxies"]);
        } catch (HttpClientException $e) {
            switch ($e->getResponse()->getStatusCode()) {
                case StatusCodes::BAD_REQUEST:
                    throw new InvalidProxyException(
                        $e->getResponse()->getBody(),
                        $e->getCode(),
                        $e
                    );
                default:
                    throw new UnexpectedStatusCodeException(
                        $e->getResponse()->getBody(),
                        $e->getCode(),
                        $e
                    );
            }
        }
    }

    /**
     * @param string $name
     * @return Proxy|null
     * @throws UnexpectedStatusCodeException
     */
    public function get($name)
    {
        try {
            $route = $this->getProxyRoute($name);
            return $this->responseToProxy($this->httpClient->request($route["method"], $route["uri"]));
        } catch (HttpClientException $e) {
            switch ($e->getResponse()->getStatusCode()) {
                case StatusCodes::NOT_FOUND:
                    return null;
                default:
                    throw new UnexpectedStatusCodeException(
                        $e->getResponse()->getBody(),
                        $e->getCode(),
                        $e
                    );
            }
        }
    }

    /**
     * @return Proxy[]
     */
    public function getAll()
    {
        $route = $this->getProxiesRoute();
        $res = $this->httpClient->request($route["method"], $route["uri"]);
        $body = json_decode($res->getBody(), true);
        return array_map(function ($contents) {
            return $this->contentsToProxy($contents);
        }, array_values($body));
    }

    /**
     * @param Proxy $proxy
     * @throws NotFoundException|UnexpectedStatusCodeException
     */
    public function delete(Proxy $proxy)
    {
        try {
            $route = $this->deleteProxyRoute($proxy);
            $this->httpClient->request($route["method"], $route["uri"]);
        } catch (HttpClientException $e) {
            switch ($e->getResponse()->getStatusCode()) {
                case StatusCodes::NOT_FOUND:
                    throw new NotFoundException(
                        $e->getResponse()->getBody(),
                        $e->getCode(),
                        $e
                    );
                default:
                    throw new UnexpectedStatusCodeException(
                        $e->getResponse()->getBody(),
                        $e->getCode(),
                        $e
                    );
            }
        }
    }

    /**
     * @param Proxy $proxy
     * @return Proxy
     * @throws NotFoundException|UnexpectedStatusCodeException
     */
    public function update(Proxy $proxy)
    {
        try {
            $route = $this->updateProxyRoute($proxy);
            return $this->responseToProxy($this->httpClient->request($route["method"], $route["uri"], [
                "body" => json_encode($proxy)
            ]));
        } catch (HttpClientException $e) {
            switch ($e->getResponse()->getStatusCode()) {
                case StatusCodes::NOT_FOUND:
                    throw new NotFoundException(
                        $e->getResponse()->getBody(),
                        $e->getCode(),
                        $e
                    );
                default:
                    throw new UnexpectedStatusCodeException(
                        $e->getResponse()->getBody(),
                        $e->getCode(),
                        $e
                    );
            }
        }
    }

    /**
     * @return void
     */
    public function reset()
    {
        $route = $this->resetRoute();
        $response = $this->httpClient->request($route["method"], $route["uri"]);
        switch ($response->getStatusCode()) {
            case StatusCodes::NO_CONTENT:
                return;
            default:
                throw new UnexpectedStatusCodeException(
                    sprintf("Unexpected status code: %s", $response->getStatusCode())
                );
        }
    }

    /**
     * @return string
     */
    public function version()
    {
        $route = $this->versionRoute();
        return $this->httpClient->request($route["method"], $route["uri"])
            ->getBody();
    }
}
