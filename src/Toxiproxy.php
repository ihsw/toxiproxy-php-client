<?php

namespace Ihsw\Toxiproxy;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException as HttpClientException;
use Ihsw\Toxiproxy\Exception\UnexpectedStatusCodeException;
use Psr\Http\Message\ResponseInterface;
use Ihsw\Toxiproxy\Exception\Exception;
use Ihsw\Toxiproxy\Exception\ProxyExistsException;
use Ihsw\Toxiproxy\Exception\NotFoundException;
use Ihsw\Toxiproxy\Exception\InvalidToxicException;

class Toxiproxy
{
    const OK = 200;
    const CREATED = 201;
    const NO_CONTENT = 204;
    const BAD_REQUEST = 400;
    const NOT_FOUND = 404;
    const CONFLICT = 409;

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
     * @param HttpClientException $e
     * @return Exception|HttpClientException
     */
    public function handleHttpClientException(HttpClientException $e)
    {
        switch ($e->getResponse()->getStatusCode()) {
            case self::CONFLICT:
                return new ProxyExistsException(
                    $e->getResponse()->getBody(),
                    $e->getCode(),
                    $e
                );
            case self::NOT_FOUND:
                return new NotFoundException(
                    $e->getResponse()->getBody(),
                    $e->getCode(),
                    $e
                );
            case self::BAD_REQUEST:
                return new InvalidToxicException(
                    $e->getResponse()->getBody(),
                    $e->getCode(),
                    $e
                );
            default:
                return new UnexpectedStatusCodeException(
                    $e->getResponse()->getBody(),
                    $e->getCode(),
                    $e
                );
        }
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
     * @return array
     */
    public function all()
    {
        return array_map(function ($contents) {
            return $this->contentsToProxy($contents);
        }, json_decode($this->httpClient->get("/proxies")->getBody(), true));
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
            return $this->responseToProxy(
                $this->httpClient->post("/proxies", [
                    "body" => json_encode(["name" => $name, "upstream" => $upstream, "listen" => $listen])
                ])
            );
        } catch (HttpClientException $e) {
            throw $this->handleHttpClientException($e);
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
            return $this->responseToProxy($this->httpClient->get(sprintf("/proxies/%s", $name)));
        } catch (HttpClientException $e) {
            if ($e->getResponse()->getStatusCode() !== self::NOT_FOUND) {
                throw $this->handleHttpClientException($e);
            }

            return null;
        }
    }

    /**
     * @param Proxy $proxy
     * @throws NotFoundException|UnexpectedStatusCodeException
     */
    public function delete(Proxy $proxy)
    {
        try {
            $this->httpClient->delete(sprintf("/proxies/%s", $proxy->getName()));
        } catch (HttpClientException $e) {
            switch ($e->getResponse()->getStatusCode()) {
                case 404:
                    throw new NotFoundException(sprintf("Proxy not found: %s", $proxy->getName()));
                default:
                    throw new UnexpectedStatusCodeException(sprintf("Unexpected status code"), 204, $e);
            }
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    public function exists($name)
    {
        return !is_null($this->get($name));
    }

    /**
     * @return ResponseInterface
     */
    public function reset()
    {
        return $this->httpClient->get("/reset");
    }

    public function deleteAll()
    {
        foreach ($this->all() as $proxy) {
            $this->delete($proxy);
        }
    }
}
