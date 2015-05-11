<?php namespace Ihsw\Toxiproxy;

use GuzzleHttp\Client as HttpClient,
    GuzzleHttp\Exception\ClientException as HttpClientException,
    GuzzleHttp\Message\Response as HttpResponse;
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

    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
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

    private function responseToProxy(HttpResponse $response)
    {
        return $this->contentsToProxy(json_decode($response->getBody(), true));
    }

    private function contentsToProxy(array $contents)
    {
        $proxy = new Proxy($this);
        $proxy->setEnabled($contents["enabled"])
            ->setName($contents["name"])
            ->setUpstream($contents["upstream"])
            ->setListen($contents["listen"]);

        if (array_key_exists("upstream_toxics", $contents)) {
            $proxy->setUpstreamToxics($contents["upstream_toxics"]);
        }
        if (array_key_exists("downstream_toxics", $contents)) {
            $proxy->setDownstreamToxics($contents["downstream_toxics"]);
        }

        return $proxy;
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
        return array_map(function($contents) {
            return $this->contentsToProxy($contents);
        }, json_decode($this->httpClient->get("/proxies")->getBody(), true));
    }

    public function create($name, $upstream, $listen = null)
    {
        try {
            return $this->responseToProxy($this->httpClient->post("/proxies", [
                "body" => json_encode([
                    "name" => $name,
                    "upstream" => $upstream,
                    "listen" => $listen
                ])
            ]));
        } catch (HttpClientException $e) {
            $this->handleHttpClientException($e);
        }
    }

    public function get($name)
    {
        try {
            return $this->responseToProxy($this->httpClient->get(sprintf("/proxies/%s", $name)));
        } catch (HttpClientException $e) {
            if ($e->getResponse()->getStatusCode() !== self::NOT_FOUND) {
                $this->handleHttpClientException($e);
            }

            return null;
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

    public function reset()
    {
        return $this->httpClient->get("/reset");
    }
}