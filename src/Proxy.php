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
    public function __construct(Toxiproxy $toxiproxy, array $content, HttpResponse $httpResponse = null)
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
     * api access
     */
    private function setToxics($toxic, $direction, $data)
    {
        $url = sprintf("proxies/%s/%s/toxics/%s",
            $this->content["name"],
            $direction,
            $toxic
        );
        try {
            return $this->getHttpClient()->post($url, ["body" => json_encode($data)]);
        } catch (HttpClientException $e) {
            $this->toxiproxy->handleHttpClientException($e);
        }
    }

    private function setProxy($data)
    {
        try {
            return $this->getHttpClient()->post(sprintf("/proxies/%s", $this->content["name"]), [
                "body" => json_encode($data)
            ]);
        } catch (HttpClientException $e) {
            $this->toxiproxy->handleHttpClientException($e);
        }
    }

    /**
     * derived api access
     */
    private function update($toxic, $direction, array $options)
    {
        $validDirections = ["upstream", "downstream"];
        if (!in_array($direction, $validDirections)) {
            throw new InvalidToxicException(sprintf("Direction must be one of: %s", implode(", ", $validDirections)));
        }

        $settings = [];
        $directionData = $this->content[sprintf("%s_toxics", $direction)];
        if (array_key_exists($toxic, $directionData)) {
            $settings = array_merge($settings, $directionData[$toxic]);
        }

        return $this->setToxics($toxic, $direction, array_merge($settings, $options));
    }

    public function updateDownstream($toxic, array $options)
    {
        return $this->update($toxic, "downstream", $options);
    }

    public function updateUpstream($toxic, array $options)
    {
        return $this->update($toxic, "upstream", $options);
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