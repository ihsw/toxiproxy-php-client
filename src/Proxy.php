<?php namespace Ihsw\Toxiproxy;

use GuzzleHttp\Message\Response as HttpResponse,
    GuzzleHttp\Exception\ClientException as HttpClientException;
use Ihsw\Toxiproxy\Toxiproxy,
    Ihsw\Toxiproxy\Exception\InvalidToxicException;

class Proxy implements \ArrayAccess
{
    const UPSTREAM = "upstream";
    const DOWNSTREAM = "downstream";

    private $toxiproxy;
    private $enabled;
    private $name;
    private $upstream;
    private $listen;
    private $upstreamToxics;
    private $downstreamToxics;

    /**
     * @param Toxiproxy
     */
    public function __construct(Toxiproxy $toxiproxy)
    {
        $this->toxiproxy = $toxiproxy;
        $this->upstreamToxics = [];
        $this->downstreamToxics = [];
    }

    /**
     * misc
     */
    private function getHttpClient()
    {
      return $this->toxiproxy->getHttpClient();
    }

    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
        return $this;
    }
    public function getEnabled() { return $this->enabled; }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
    public function getName() { return $this->name; }

    public function setUpstream($upstream)
    {
        $this->upstream = $upstream;
        return $this;
    }
    public function getUpstream() { return $this->upstream; }

    public function setListen($listen)
    {
        $this->listen = $listen;
        return $this;
    }
    public function getListen() { return $this->listen; }
    public function getListenIp()
    {
        $ip = implode(":", explode(":", $this->listen, -1));
        if (substr($ip, 0, 1) === "[") {
            $ip = substr($ip, 1, -1);
        }
        return $ip;
    }
    public function getListenPort()
    {
        $ip = $this->getListenIp();
        $start = substr($this->listen, 0, 1) === "[" ? 3 : 1;
        return substr($this->listen, $start + strlen($ip));
    }

    public function setUpstreamToxics(array $toxics)
    {
        $this->upstreamToxics = $toxics;
        return $this;
    }
    public function getUpstreamToxics() { return $this->upstreamToxics; }

    public function setDownstreamToxics(array $toxics)
    {
        $this->downstreamToxics = $toxics;
        return $this;
    }
    public function getDownstreamToxics() { return $this->downstreamToxics; }


    /**
     * ArrayAccess
     */
    public function offsetExists($offset)
    {
        throw new \Exception("NYI");
    }

    public function offsetSet($offset, $value)
    {
        throw new \Exception("NYI");
    }

    public function offsetUnset($offset)
    {
        throw new \Exception("NYI");
        unset($this->content[$offset]);
    }

    public function offsetGet($offset)
    {
        throw new \Exception("NYI");
        return $this->content[$offset];
    }

    /**
     * api access
     */
    private function setToxic($toxic, $direction, $data)
    {
        $url = sprintf("proxies/%s/%s/toxics/%s",
            $this->name,
            $direction,
            $toxic
        );
        try {
            return $this->getHttpClient()->post($url, [
                "body" => json_encode($data)
            ]);
        } catch (HttpClientException $e) {
            $this->toxiproxy->handleHttpClientException($e);
        }
    }

    private function setProxy($data)
    {
        try {
            return $this->getHttpClient()->post(
                sprintf("/proxies/%s", $this->name),
                ["body" => json_encode($data)]
            );
        } catch (HttpClientException $e) {
            $this->toxiproxy->handleHttpClientException($e);
        }
    }

    /**
     * derived api access
     */
    private function update($toxic, $direction, array $options)
    {
        $validDirections = [self::UPSTREAM, self::DOWNSTREAM];
        if (!in_array($direction, $validDirections)) {
            throw new InvalidToxicException(sprintf(
                "Direction must be one of: %s",
                implode(", ", $validDirections)
            ));
        }

        $settings = [];
        $key = sprintf("%sToxics", $direction);
        $directionData = $this->$key;
        if (array_key_exists($toxic, $directionData)) {
            $settings = array_merge($settings, $directionData[$toxic]);
        }

        return $this->setToxic(
            $toxic,
            $direction,
            array_merge($settings, $options)
        );
    }

    public function updateDownstream($toxic, array $options)
    {
        return $this->update($toxic, self::DOWNSTREAM, $options);
    }

    public function updateUpstream($toxic, array $options)
    {
        return $this->update($toxic, self::UPSTREAM, $options);
    }

    public function disable()
    {
        return $this->setProxy(["enabled" => false]);
    }

    public function enable()
    {
        return $this->setProxy(["enabled" => true]);
    }
}
