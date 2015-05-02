<?php namespace Ihsw\Toxiproxy;

use GuzzleHttp\Message\Response as HttpResponse,
    GuzzleHttp\Exception\ClientException as HttpClientException;
use Ihsw\Toxiproxy\Toxiproxy,
    Ihsw\Toxiproxy\Exception\InvalidToxicException;

class Proxy implements \ArrayAccess
{
    private $toxiproxy;
    private $content;
    private $httpResponse;

    /**
     * @param Toxiproxy
     * @param array
     * @param HttpResponse|null
     */
    public function __construct(Toxiproxy $toxiproxy, $content, HttpResponse $httpResponse = null)
    {
        $this->toxiproxy = $toxiproxy;
        $this->content = $content;
        $this->httpResponse = $httpResponse;
    }

    /**
     * misc
     */
    public function getHttpResponse()
    {
        return $this->httpResponse;
    }

    private function getHttpClient()
    {
        return $this->toxiproxy->getHttpClient();
    }

    /**
     * ArrayAccess
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offfset, $this->content);
    }

    public function offsetSet($offset, $value)
    {
        $this->content[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->content[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->content[$offset];
    }

    /**
     * crud
     */
    public function update($name, $direction, array $options)
    {
        $toxicSettings = [
            "latency" => ["enabled" => true, "latency" => 0, "jitter" => 0],
            "slow_close" => ["enabled" => true, "delay" => 0],
            "timeout" => ["enabled" => true, "timeout" => 0]
        ];
        if (!array_key_exists($name, $toxicSettings)) {
            throw new InvalidToxicException(sprintf("Toxic %s could not be found", $name));
        }

        $settings = array_merge($toxicSettings[$name], $options);
        $url = sprintf("proxies/%s/%s/toxics/%s",
            $this->content["name"],
            $direction,
            $name
        );
        try {
            return $this->getHttpClient()->post($url, ["body" => json_encode($settings)]);
        } catch (HttpClientException $e) {
            $this->toxiproxy->handleHttpClientException($e);
        }
    }
}