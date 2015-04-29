<?php namespace Ihsw\Toxiproxy;

use GuzzleHttp\Client as HttpClient,
    GuzzleHttp\Exception\ClientException as HttpClientException;
use Ihsw\Toxiproxy\Exception\ProxyExistsException,
    Ihsw\Toxiproxy\Exception\NotFoundException;

class Toxiproxy
{
    const OK = 200;
    const CREATED = 201;
    const NOT_FOUND = 404;
    const CONFLICT = 409;
    const NO_CONTENT = 204;

    private $httpClient;

    public function __construct()
    {
        $this->httpClient = new HttpClient(["base_url" => "http://127.0.0.1:8474"]);
    }

    private function handleHttpClientException(HttpClientException $e)
    {
        switch ($e->getResponse()->getStatusCode())
        {
            case self::CONFLICT:
                throw new ProxyExistsException($e->getResponse()->getBody(), $e->getCode(), $e);
                break;
            case self::NOT_FOUND:
                throw new NotFoundException($e->getResponse()->getBody(), $e->getCode(), $e);
                break;
            default:
                throw $e;
        }
    }

    /**
     * crud
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }

    public function all()
    {
        return json_decode($this->httpClient->get("/proxies")->getBody(), true);
    }

    public function create($name, $upstream, $listen)
    {
        try {
            return $this->httpClient->post("/proxies", [
                "body" => json_encode([
                    "name" => $name,
                    "upstream" => $upstream,
                    "listen" => $listen
                ])
            ]);
        }
        catch (HttpClientException $e) {
            $this->handleHttpClientException($e);
        }
    }

    public function get($name)
    {
        try {
            return $this->httpClient->get(sprintf("/proxies/%s", $name));
        }
        catch (HttpClientException $e) {
            $this->handleHttpClientException($e);
        }
    }

    public function delete($name)
    {
        try {
            return $this->httpClient->delete(sprintf("/proxies/%s", $name));
        }
        catch (HttpClientException $e) {
            $this->handleHttpClientException($e);
        }
    }
}