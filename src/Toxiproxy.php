<?php namespace Ihsw\Toxiproxy;

use GuzzleHttp\Client as HttpClient,
    GuzzleHttp\Exception\ClientException as HttpClientException;
use Ihsw\Toxiproxy\Exception\ProxyExistsException,
    Ihsw\Toxiproxy\Exception\NotFoundException,
    Ihsw\Toxiproxy\Exception\InvalidToxicException,
    Ihsw\Toxiproxy\Proxy;

class Toxiproxy implements \ArrayAccess
{
    const OK = 200;
    const CREATED = 201;
    const NO_CONTENT = 204;
    const BAD_REQUEST = 400;
    const NOT_FOUND = 404;
    const CONFLICT = 409;

    private $httpClient;

    public function __construct($baseUrl)
    {
        $this->httpClient = new HttpClient(["base_url" => $baseUrl]);
    }

    public function handleHttpClientException(HttpClientException $e)
    {
        switch ($e->getResponse()->getStatusCode()) {
            case self::CONFLICT:
                throw new ProxyExistsException($e->getResponse()->getBody(), $e->getCode(), $e);
                break;
            case self::NOT_FOUND:
                throw new NotFoundException($e->getResponse()->getBody(), $e->getCode(), $e);
                break;
            case self::BAD_REQUEST:
                throw new InvalidToxicException($e->getResponse()->getBody(),  $e->getCode(), $e);
            default:
                throw $e;
        }
    }

    /**
     * misc
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }

    /**
     * ArrayAccess
     */
    public function offsetExists($offset)
    {
        try {
            $this->get($offset);
            return true;
        } catch (NotFoundException $e) {
            return false;
        }
    }

    public function offsetSet($offset, $value)
    {
        array_unshift($value, $offset);
        call_user_func_array([$this, "create"], $value);
    }

    public function offsetUnset($offset)
    {
        $this->delete($offset);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * crud
     */
    public function all()
    {
        return array_map(function($content) {
            return new Proxy($this, $content);
        }, json_decode($this->httpClient->get("/proxies")->getBody(), true));
    }

    public function create($name, $upstream, $listen)
    {
        try {
            $response = $this->httpClient->post("/proxies", [
                "body" => json_encode([
                    "name" => $name,
                    "upstream" => $upstream,
                    "listen" => $listen
                ])
            ]);
            return new Proxy($this, json_decode($response->getBody(), true), $response);
        } catch (HttpClientException $e) {
            $this->handleHttpClientException($e);
        }
    }

    public function get($name)
    {
        try {
            $response = $this->httpClient->get(sprintf("/proxies/%s", $name));
            return new Proxy($this, json_decode($response->getBody(), true), $response);
        } catch (HttpClientException $e) {
            $this->handleHttpClientException($e);
        }
    }

    public function delete(Proxy $proxy)
    {
        try {
            return $this->httpClient->delete(sprintf("/proxies/%s", $proxy["name"]));
        } catch (HttpClientException $e) {
            $this->handleHttpClientException($e);
        }
    }

    public function exists($name)
    {
        return $this->offsetExists($name);
    }
}