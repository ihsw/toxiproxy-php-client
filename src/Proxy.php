<?php namespace Ihsw\Toxiproxy;

use GuzzleHttp\Message\Response as HttpResponse;
use Ihsw\Toxiproxy\Toxiproxy;

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
}