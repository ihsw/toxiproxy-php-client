<?php

namespace Ihsw\Toxiproxy;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Ihsw\Toxiproxy\Exception\ProxyExistsException;
use Ihsw\Toxiproxy\Exception\InvalidProxyException;
use Ihsw\Toxiproxy\Exception\NotFoundException;
use Ihsw\Toxiproxy\Exception\UnexpectedStatusCodeException;

class Toxiproxy
{
    use UrlHelpers;

    /**
     * @var Client
     */
    private $httpClient;

    /**
     * Toxiproxy constructor.
     * @param string $baseUrl
     */
    public function __construct($baseUrl)
    {
        $this->httpClient = new Client([
            "base_uri" => $baseUrl,
            "http_errors" => false
        ]);
    }

    /**
     * @return Client
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }

    /**
     * @param Client $client
     * @return $this
     */
    public function setHttpClient(Client $client)
    {
        $this->httpClient = $client;
        return $this;
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
        $route = $this->createProxyRoute();
        $response = $this->httpClient->request($route["method"], $route["uri"], [
            "body" => json_encode(["name" => $name, "upstream" => $upstream, "listen" => $listen])
        ]);
        switch ($response->getStatusCode()) {
            case StatusCodes::CREATED:
                return $this->responseToProxy($response);
            case StatusCodes::CONFLICT:
                throw new ProxyExistsException($response->getBody());
            default:
                throw new UnexpectedStatusCodeException(sprintf(
                    "%s: %s",
                    $response->getStatusCode(),
                    $response->getBody()
                ));
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
        $response = $this->httpClient->request($route["method"], $route["uri"], [
            "body" => json_encode($proxyBodies)
        ]);
        switch ($response->getStatusCode()) {
            case StatusCodes::CREATED:
                $contents = json_decode($response->getBody(), true);

                return array_map(function ($contents) {
                    return $this->contentsToProxy($contents);
                }, $contents["proxies"]);
            case StatusCodes::BAD_REQUEST:
                throw new InvalidProxyException($response->getBody());
            default:
                throw new UnexpectedStatusCodeException(sprintf(
                    "%s: %s",
                    $response->getStatusCode(),
                    $response->getBody()
                ));
        }
    }

    /**
     * @param string $name
     * @return Proxy|null
     * @throws UnexpectedStatusCodeException
     */
    public function get($name)
    {
        $route = $this->getProxyRoute($name);
        $response = $this->httpClient->request($route["method"], $route["uri"]);
        switch ($response->getStatusCode()) {
            case StatusCodes::OK:
                return $this->responseToProxy($response);
            case StatusCodes::NOT_FOUND:
                return null;
            default:
                throw new UnexpectedStatusCodeException(sprintf(
                    "%s: %s",
                    $response->getStatusCode(),
                    $response->getBody()
                ));
        }
    }

    /**
     * @return Proxy[]
     * @throws UnexpectedStatusCodeException
     */
    public function getAll()
    {
        $route = $this->getProxiesRoute();
        $response = $this->httpClient->request($route["method"], $route["uri"]);
        switch ($response->getStatusCode()) {
            case StatusCodes::OK:
                $body = json_decode($response->getBody(), true);

                return array_map(function ($contents) {
                    return $this->contentsToProxy($contents);
                }, array_values($body));
            default:
                throw new UnexpectedStatusCodeException(sprintf(
                    "%s: %s",
                    $response->getStatusCode(),
                    $response->getBody()
                ));
        }
    }

    /**
     * @param Proxy $proxy
     * @throws NotFoundException|UnexpectedStatusCodeException
     */
    public function delete(Proxy $proxy)
    {
        $route = $this->deleteProxyRoute($proxy);
        $response = $this->httpClient->request($route["method"], $route["uri"]);
        switch ($response->getStatusCode()) {
            case StatusCodes::NO_CONTENT:
                return;
            case StatusCodes::NOT_FOUND:
                throw new NotFoundException($response->getBody());
            default:
                throw new UnexpectedStatusCodeException(sprintf(
                    "%s: %s",
                    $response->getStatusCode(),
                    $response->getBody()
                ));
        }
    }

    /**
     * @param Proxy $proxy
     * @return Proxy
     * @throws NotFoundException|UnexpectedStatusCodeException
     */
    public function update(Proxy $proxy)
    {
        $route = $this->updateProxyRoute($proxy);
        $response = $this->httpClient->request($route["method"], $route["uri"], [
            "body" => json_encode($proxy)
        ]);
        switch ($response->getStatusCode()) {
            case StatusCodes::OK:
                return $this->responseToProxy($response);
            case StatusCodes::NOT_FOUND:
                throw new NotFoundException($response->getBody());
            default:
                throw new UnexpectedStatusCodeException(sprintf(
                    "%s: %s",
                    $response->getStatusCode(),
                    $response->getBody()
                ));
        }
    }

    /**
     * @return void
     * @throws UnexpectedStatusCodeException
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
     * @throws UnexpectedStatusCodeException
     */
    public function version()
    {
        $route = $this->versionRoute();
        $response = $this->httpClient->request($route["method"], $route["uri"]);
        switch ($response->getStatusCode()) {
            case StatusCodes::OK:
                return $response->getBody();
            default:
                throw new UnexpectedStatusCodeException(sprintf(
                    "%s: %s",
                    $response->getStatusCode(),
                    $response->getBody()
                ));
        }
    }
}
