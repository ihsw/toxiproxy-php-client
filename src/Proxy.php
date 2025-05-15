<?php

declare(strict_types=1);

namespace Ihsw\Toxiproxy;

use GuzzleHttp\Client;
use Ihsw\Toxiproxy\Exception\NotFoundException;
use Ihsw\Toxiproxy\Exception\ToxicExistsException;
use Ihsw\Toxiproxy\Exception\UnexpectedStatusCodeException;
use JsonSerializable;
use Psr\Http\Message\ResponseInterface;

class Proxy implements JsonSerializable
{
    use UrlHelpers;

    private Toxiproxy $toxiproxy;
    private string $name;
    private string $listen;
    private string $upstream;
    private bool $enabled;
    /**
     * @var Toxic[]
     */
    private array $toxics;

    /**
     * @param array{
     *     name: string,
     *     type: string,
     *     stream: string,
     *     toxicity: float,
     *     attributes: array<string, mixed>
     * }[] $toxicContents
     */
    public function __construct(Toxiproxy $toxiproxy, string $name, array $toxicContents = [])
    {
        $this->toxiproxy = $toxiproxy;
        $this->name = $name;
        $this->toxics = array_map(
            function ($toxicContent) {
                return $this->contentsToToxic($toxicContent);
            },
            $toxicContents,
        );
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
        /**
         * @var array{
         *     name: string,
         *     type: string,
         *     stream: string,
         *     toxicity: float,
         *     attributes: array<string, mixed>
         * } $toxicContents
         */
        $toxicContents = (array) json_decode((string) $response->getBody(), true);
        return $this->contentsToToxic($toxicContents);
    }

    /**
     * @param array{
     *     name: string,
     *     type: string,
     *     stream: string,
     *     toxicity: float,
     *     attributes: array<string, mixed>
     * } $contents
     */
    private function contentsToToxic(array $contents): Toxic
    {
        $toxic = new Toxic(
            $this,
            $contents['name'],
            $contents['type'],
            $contents['stream'],
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
        $response = $this->getHttpClient()->request((string) $route['method'], (string) $route['uri']);
        switch ($response->getStatusCode()) {
            case StatusCodes::OK->value:
                /**
                 * @var array{
                 *     name: string,
                 *     type: string,
                 *     stream: string,
                 *     toxicity: float,
                 *     attributes: array<string, mixed>
                 * }[] $body
                 */
                $body = (array) json_decode((string) $response->getBody(), true);

                return array_map(
                    function ($contents) {
                        return $this->contentsToToxic($contents);
                    },
                    array_values($body),
                );
            default:
                throw new UnexpectedStatusCodeException(
                    sprintf(
                        '%s: %s',
                        $response->getStatusCode(),
                        $response->getBody(),
                    ),
                );
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
        $response = $this->getHttpClient()->request(
            (string) $route['method'],
            (string) $route['uri'],
            [
            'body' => json_encode(
                [
                'name' => $name,
                'stream' => $stream,
                'type' => $type,
                'toxicity' => $toxicity,
                'attributes' => $attributes,
                ],
            ),
            ],
        );
        switch ($response->getStatusCode()) {
            case StatusCodes::OK->value:
            case StatusCodes::NO_CONTENT->value:
                return $this->responseToToxic($response);
            case StatusCodes::CONFLICT->value:
                throw new ToxicExistsException((string) $response->getBody());
            default:
                throw new UnexpectedStatusCodeException(
                    sprintf(
                        '%s: %s',
                        $response->getStatusCode(),
                        $response->getBody(),
                    ),
                );
        }
    }

    /**
     * @throws UnexpectedStatusCodeException
     */
    public function get(string $name): ?Toxic
    {
        $route = $this->getToxicRoute($this, $name);
        $response = $this->getHttpClient()->request((string) $route['method'], (string) $route['uri']);
        switch ($response->getStatusCode()) {
            case StatusCodes::OK->value:
                return $this->responseToToxic($response);
            case StatusCodes::NOT_FOUND->value:
                return null;
            default:
                throw new UnexpectedStatusCodeException(
                    sprintf(
                        '%s: %s',
                        $response->getStatusCode(),
                        $response->getBody(),
                    ),
                );
        }
    }

    /**
     * @throws NotFoundException|UnexpectedStatusCodeException
     */
    public function update(Toxic $toxic): Toxic
    {
        $route = $this->updateToxicRoute($this, $toxic);
        $response = $this->getHttpClient()->request(
            (string) $route['method'],
            (string) $route['uri'],
            ['body' => json_encode($toxic)],
        );
        switch ($response->getStatusCode()) {
            case StatusCodes::OK->value:
                return $this->responseToToxic($response);
            case StatusCodes::NOT_FOUND->value:
                throw new NotFoundException((string) $response->getBody());
            default:
                throw new UnexpectedStatusCodeException(
                    sprintf(
                        '%s: %s',
                        $response->getStatusCode(),
                        $response->getBody(),
                    ),
                );
        }
    }

    /**
     * @throws NotFoundException|UnexpectedStatusCodeException
     */
    public function delete(Toxic $toxic): void
    {
        $route = $this->deleteToxicRoute($this, $toxic);
        $response = $this->getHttpClient()->request((string) $route['method'], (string) $route['uri']);
        switch ($response->getStatusCode()) {
            case StatusCodes::NO_CONTENT->value:
                return;
            case StatusCodes::NOT_FOUND->value:
                throw new NotFoundException((string) $response->getBody());
            default:
                throw new UnexpectedStatusCodeException(
                    sprintf(
                        '%s: %s',
                        $response->getStatusCode(),
                        $response->getBody(),
                    ),
                );
        }
    }

    public function getListenIp(): string
    {
        $ip = implode(':', explode(':', $this->listen, -1));
        if (str_starts_with($ip, '[')) {
            $ip = substr($ip, 1, -1);
        }
        return $ip;
    }

    public function getListenPort(): string
    {
        $ip = $this->getListenIp();
        $start = str_starts_with($this->listen, '[') ? 3 : 1;
        return substr($this->listen, $start + strlen($ip));
    }
}
