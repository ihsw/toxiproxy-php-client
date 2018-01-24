<?php

namespace Ihsw\Toxiproxy;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Ihsw\Toxiproxy\Exception\NotFoundException;
use Ihsw\Toxiproxy\Exception\ToxicExistsException;
use Ihsw\Toxiproxy\Exception\UnexpectedStatusCodeException;

class Proxy implements \JsonSerializable
{
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
     * @param string $name
     * @param array $toxicContents
     */
    public function __construct(Toxiproxy $toxiproxy, $name, array $toxicContents = [])
    {
        $this->toxiproxy = $toxiproxy;
        $this->name = $name;
        $this->toxics = array_map(function ($toxicContent) {
            return $this->contentsToToxic($toxicContent);
        }, $toxicContents);
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
     * @return Toxic[]
     */
    public function getToxics()
    {
        return $this->toxics;
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
    private function responseToToxic(ResponseInterface $response)
    {
        return $this->contentsToToxic(json_decode($response->getBody(), true));
    }

    /**
     * @param array $contents
     * @return Toxic
     */
    private function contentsToToxic(array $contents)
    {
        $toxic = new Toxic(
            $this,
            $contents["name"],
            $contents["type"],
            $contents["stream"]
        );
        $toxic->setToxicity($contents["toxicity"])
            ->setAttributes($contents["attributes"]);

        return $toxic;
    }

    /**
     * @return Client
     */
    private function getHttpClient()
    {
        return $this->toxiproxy->getHttpClient();
    }

    /**
     * @return Toxic[]
     */
    public function getAll()
    {
        $route = $this->getToxicsRoute($this);
        $response = $this->getHttpClient()->request($route["method"], $route["uri"]);
        switch ($response->getStatusCode()) {
            case StatusCodes::OK:
                $body = json_decode($response->getBody(), true);

                return array_map(function ($contents) {
                    return $this->contentsToToxic($contents);
                }, array_values($body));
            default:
                throw new UnexpectedStatusCodeException(sprintf(
                    "%s: %s",
                    $response->getStatusCode(),
                    $response->getBody()
                ));
        }
    }

    /**
     * @param string $type
     * @param string $stream
     * @param string $toxicity
     * @param array $attributes
     * @param string|null $name
     * @return Toxic
     * @throws ToxicExistsException|UnexpectedStatusCodeException
     */
    public function create($type, $stream, $toxicity, $attributes, $name = null)
    {
        $route = $this->createToxicRoute($this);
        $response = $this->getHttpClient()->request($route["method"], $route["uri"], [
            "body" => json_encode([
                "name" => $name,
                "stream" => $stream,
                "type" => $type,
                "toxicity" => $toxicity,
                "attributes" => $attributes
            ])
        ]);
        switch ($response->getStatusCode()) {
            case StatusCodes::OK:
            case StatusCodes::NO_CONTENT:
                return $this->responseToToxic($response);
            case StatusCodes::CONFLICT:
                throw new ToxicExistsException($response->getBody());
            default:
                throw new UnexpectedStatusCodeException(sprintf(
                    "%s: %s",
                    $response->getStatusCode(),
                    $response->getBody()
                ));
        }
    }

    /**
     * @param string $name
     * @return Toxic|null
     * @throws UnexpectedStatusCodeException
     */
    public function get($name)
    {
        $route = $this->getToxicRoute($this, $name);
        $response = $this->getHttpClient()->request($route["method"], $route["uri"]);
        switch ($response->getStatusCode()) {
            case StatusCodes::OK:
                return $this->responseToToxic($response);
            case StatusCodes::NOT_FOUND:
                return null;
            default:
                throw new UnexpectedStatusCodeException(sprintf(
                    "%s: %s",
                    $response->getStatusCode(),
                    $response->getBody()
                ));
        }
    }

    /**
     * @param Toxic $toxic
     * @return Toxic
     * @throws NotFoundException|UnexpectedStatusCodeException
     */
    public function update(Toxic $toxic)
    {
        $route = $this->updateToxicRoute($this, $toxic);
        $response = $this->getHttpClient()->request(
            $route["method"],
            $route["uri"],
            ["body" => json_encode($toxic)]
        );
        switch ($response->getStatusCode()) {
            case StatusCodes::OK:
                return $this->responseToToxic($response);
            case StatusCodes::NOT_FOUND:
                throw new NotFoundException($response->getBody());
            default:
                throw new UnexpectedStatusCodeException(sprintf(
                    "%s: %s",
                    $response->getStatusCode(),
                    $response->getBody()
                ));
        }
    }

    /**
     * @param Toxic $toxic
     * @return void
     * @throws NotFoundException|UnexpectedStatusCodeException
     */
    public function delete(Toxic $toxic)
    {
        $route = $this->deleteToxicRoute($this, $toxic);
        $response = $this->getHttpClient()->request($route["method"], $route["uri"]);
        switch ($response->getStatusCode()) {
            case StatusCodes::NO_CONTENT:
                return;
            case StatusCodes::NOT_FOUND:
                throw new NotFoundException($response->getBody());
            default:
                throw new UnexpectedStatusCodeException(sprintf(
                    "%s: %s",
                    $response->getStatusCode(),
                    $response->getBody()
                ));
        }
    }
}
