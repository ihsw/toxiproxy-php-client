<?php

namespace Ihsw\Toxiproxy;

use GuzzleHttp\Client;
use JsonSerializable;
use Psr\Http\Message\ResponseInterface;
use Ihsw\Toxiproxy\Exception\NotFoundException;
use Ihsw\Toxiproxy\Exception\ToxicExistsException;
use Ihsw\Toxiproxy\Exception\UnexpectedStatusCodeException;

class Proxy implements JsonSerializable
{
    use UrlHelpers;
    use ListenHelpers;

    private Toxiproxy $toxiproxy;
    private string $name;
    private string $listen;
    private string $upstream;
    private bool $enabled;
    /**
     * @var Toxic[]
     */
    private array $toxics;

    public function __construct(Toxiproxy $toxiproxy, string $name, array $toxicContents = [])
    {
        $this->toxiproxy = $toxiproxy;
        $this->name = $name;
        $this->toxics = array_map(function ($toxicContent) {
            return $this->contentsToToxic($toxicContent);
        }, $toxicContents);
        $this->enabled = false;
        $this->upstream = '';
        $this->listen = '';
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setListen(string $listen): self
    {
        $this->listen = $listen;
        return $this;
    }

    public function getListen(): string
    {
        return $this->listen;
    }

    public function getUpstream(): string
    {
        return $this->upstream;
    }

    public function setUpstream(string $upstream): self
    {
        $this->upstream = $upstream;
        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;
        return $this;
    }

    /**
     * @return Toxic[]
     */
    public function getToxics(): array
    {
        return $this->toxics;
    }

    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'listen' => $this->listen,
            'upstream' => $this->upstream,
            'enabled' => $this->enabled,
        ];
    }

    private function responseToToxic(ResponseInterface $response): Toxic
    {
        return $this->contentsToToxic(json_decode($response->getBody(), true));
    }

    private function contentsToToxic(array $contents): Toxic
    {
        $toxic = new Toxic(
            $this,
            $contents['name'],
            $contents['type'],
            $contents['stream']
        );
        $toxic->setToxicity($contents['toxicity'])
            ->setAttributes($contents['attributes']);

        return $toxic;
    }

    private function getHttpClient(): Client
    {
        return $this->toxiproxy->getHttpClient();
    }

    /**
     * @return Toxic[]
     */
    public function getAll(): array
    {
        $route = $this->getToxicsRoute($this);
        $response = $this->getHttpClient()->request($route['method'], $route['uri']);
        switch ($response->getStatusCode()) {
            case StatusCodes::OK->value:
                $body = json_decode($response->getBody(), true);

                return array_map(function ($contents) {
                    return $this->contentsToToxic($contents);
                }, array_values($body));
            default:
                throw new UnexpectedStatusCodeException(sprintf(
                    '%s: %s',
                    $response->getStatusCode(),
                    $response->getBody()
                ));
        }
    }

    /**
     * @throws ToxicExistsException|UnexpectedStatusCodeException
     */
    public function create(
        string $type,
        string $stream,
        float $toxicity,
        array $attributes,
        ?string $name = null,
    ): Toxic {
        $route = $this->createToxicRoute($this);
        $response = $this->getHttpClient()->request($route['method'], $route['uri'], [
            'body' => json_encode([
                'name' => $name,
                'stream' => $stream,
                'type' => $type,
                'toxicity' => $toxicity,
                'attributes' => $attributes,
            ])
        ]);
        switch ($response->getStatusCode()) {
            case StatusCodes::OK->value:
            case StatusCodes::NO_CONTENT->value:
                return $this->responseToToxic($response);
            case StatusCodes::CONFLICT->value:
                throw new ToxicExistsException($response->getBody());
            default:
                throw new UnexpectedStatusCodeException(sprintf(
                    '%s: %s',
                    $response->getStatusCode(),
                    $response->getBody()
                ));
        }
    }

    /**
     * @throws UnexpectedStatusCodeException
     */
    public function get(string $name): ?Toxic
    {
        $route = $this->getToxicRoute($this, $name);
        $response = $this->getHttpClient()->request($route['method'], $route['uri']);
        switch ($response->getStatusCode()) {
            case StatusCodes::OK->value:
                return $this->responseToToxic($response);
            case StatusCodes::NOT_FOUND->value:
                return null;
            default:
                throw new UnexpectedStatusCodeException(sprintf(
                    '%s: %s',
                    $response->getStatusCode(),
                    $response->getBody()
                ));
        }
    }

    /**
     * @throws NotFoundException|UnexpectedStatusCodeException
     */
    public function update(Toxic $toxic): Toxic
    {
        $route = $this->updateToxicRoute($this, $toxic);
        $response = $this->getHttpClient()->request(
            $route['method'],
            $route['uri'],
            ['body' => json_encode($toxic)]
        );
        switch ($response->getStatusCode()) {
            case StatusCodes::OK->value:
                return $this->responseToToxic($response);
            case StatusCodes::NOT_FOUND->value:
                throw new NotFoundException($response->getBody());
            default:
                throw new UnexpectedStatusCodeException(sprintf(
                    '%s: %s',
                    $response->getStatusCode(),
                    $response->getBody()
                ));
        }
    }

    /**
     * @throws NotFoundException|UnexpectedStatusCodeException
     */
    public function delete(Toxic $toxic): void
    {
        $route = $this->deleteToxicRoute($this, $toxic);
        $response = $this->getHttpClient()->request($route['method'], $route['uri']);
        switch ($response->getStatusCode()) {
            case StatusCodes::NO_CONTENT->value:
                return;
            case StatusCodes::NOT_FOUND->value:
                throw new NotFoundException($response->getBody());
            default:
                throw new UnexpectedStatusCodeException(sprintf(
                    '%s: %s',
                    $response->getStatusCode(),
                    $response->getBody()
                ));
        }
    }
}
