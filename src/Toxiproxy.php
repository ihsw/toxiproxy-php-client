<?php namespace Ihsw\Toxiproxy;

use GuzzleHttp\Client as HttpClient,
    GuzzleHttp\Exception\ClientException as HttpClientException;
use Ihsw\Toxiproxy\Exception\ProxyExistsException,
    Ihsw\Toxiproxy\Exception\NotFoundException,
    Ihsw\Toxiproxy\Proxy;

class Toxiproxy implements \ArrayAccess
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
        switch ($e->getResponse()->getStatusCode()) {
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

    public function delete($name)
    {
        try {
            return $this->httpClient->delete(sprintf("/proxies/%s", $name));
        } catch (HttpClientException $e) {
            $this->handleHttpClientException($e);
        }
    }
}