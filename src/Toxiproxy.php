<?php

namespace Ihsw\Toxiproxy;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException as HttpClientException;
use Psr\Http\Message\ResponseInterface;
use Ihsw\Toxiproxy\Exception\Exception;
use Ihsw\Toxiproxy\Exception\ProxyExistsException;
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
        $proxy = new Proxy($this);
        $proxy->setEnabled($contents["enabled"])
            ->setName($contents["name"])
            ->setUpstream($contents["upstream"])
            ->setListen($contents["listen"]);

        return $proxy;
    }

    /**
     * @param string $name
     * @param string $upstream
     * @param string|null $listen
     * @return Proxy
     * @throws Exception|HttpClientException
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
     * @param string $name
     * @return Proxy|null
     * @throws Exception|HttpClientException
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
}
