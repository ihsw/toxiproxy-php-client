<?php

namespace Ihsw\Toxiproxy;

use GuzzleHttp\Exception\ClientException as HttpClientException;
use Psr\Http\Message\ResponseInterface;
use Ihsw\Toxiproxy\Exception\ToxicExistsException;
use Ihsw\Toxiproxy\Exception\UnexpectedStatusCodeException;

class Proxy implements \JsonSerializable
{
    const UPSTREAM = "upstream";
    const DOWNSTREAM = "downstream";

    use UrlHelpers;

    /**
     * @var Toxiproxy
     */
    private $toxiproxy;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $listen;

    /**
     * @var string
     */
    private $upstream;

    /**
     * @var bool
     */
    private $enabled;

    /**
     * @var Toxic[]
     */
    private $toxics;

    use ListenHelpers;

    /**
     * Proxy constructor.
     * @param Toxiproxy $toxiproxy
     */
    public function __construct(Toxiproxy $toxiproxy)
    {
        $this->toxiproxy = $toxiproxy;
        $this->toxics = [];
    }

    /**
     * @param $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $listen
     * @return $this
     */
    public function setListen($listen)
    {
        $this->listen = $listen;
        return $this;
    }

    /**
     * @return string
     */
    public function getListen()
    {
        return $this->listen;
    }

    /**
     * @return string
     */
    public function getUpstream()
    {
        return $this->upstream;
    }

    /**
     * @param string $upstream
     * @return $this
     */
    public function setUpstream($upstream)
    {
        $this->upstream = $upstream;
        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     * @return $this
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
        return $this;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            "name" => $this->name,
            "listen" => $this->listen,
            "upstream" => $this->upstream,
            "enabled" => $this->enabled
        ];
    }

    /**
     * @param ResponseInterface $response
     * @return Toxic
     */
    public function responseToToxic(ResponseInterface $response)
    {
        return $this->contentsToToxic(json_decode($response->getBody(), true));
    }

    /**
     * @param array $contents
     * @return Toxic
     */
    private function contentsToToxic(array $contents)
    {
        $toxic = new Toxic($this);
        $toxic->setName($contents["name"])
            ->setType($contents["type"])
            ->setStream($contents["stream"])
            ->setToxicity($contents["toxicity"])
            ->setAttributes($contents["attributes"]);

        return $toxic;
    }

    /**
     * @return \GuzzleHttp\Client
     */
    public function getHttpClient()
    {
        return $this->toxiproxy->getHttpClient();
    }

    /**
     * @return Toxic[]
     */
    public function getAll()
    {
        $route = $this->getToxicsRoute($this);
        $res = $this->getHttpClient()->request($route["method"], $route["uri"]);
        $body = json_decode($res->getBody(), true);
        return array_map(function ($contents) {
            return $this->contentsToToxic($contents);
        }, array_values($body));
    }

    public function create($type, $stream, $toxicity, $attributes, $name = null)
    {
        try {
            $route = $this->createToxicRoute($this);
            return $this->responseToToxic(
                $this->getHttpClient()->request($route["method"], $route["uri"], [
                    "body" => json_encode([
                        "name" => $name,
                        "stream" => $stream,
                        "type" => $type,
                        "toxicity" => $toxicity,
                        "attributes" => $attributes
                    ])
                ])
            );
        } catch (HttpClientException $e) {
            switch ($e->getResponse()->getStatusCode()) {
                case StatusCodes::CONFLICT:
                    throw new ToxicExistsException(
                        $e->getResponse()->getBody(),
                        $e->getCode(),
                        $e
                    );
                default:
                    throw new UnexpectedStatusCodeException(
                        $e->getResponse()->getBody(),
                        $e->getCode(),
                        $e
                    );
            }
        }
    }
}
