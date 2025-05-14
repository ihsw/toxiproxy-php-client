<?php

declare(strict_types=1);

namespace Ihsw\Toxiproxy;

use GuzzleHttp\Client;
use Ihsw\Toxiproxy\Exception\InvalidProxyException;
use Ihsw\Toxiproxy\Exception\NotFoundException;
use Ihsw\Toxiproxy\Exception\ProxyExistsException;
use Ihsw\Toxiproxy\Exception\UnexpectedStatusCodeException;
use Psr\Http\Message\ResponseInterface;

class Toxiproxy
{
    use UrlHelpers;


    private Client $httpClient;

    public function __construct(string $baseUrl)
    {
        $this->httpClient = new Client(
            [
            'base_uri' => $baseUrl,
            'http_errors' => false,
            ],
        );
    }

    public function getHttpClient(): Client
    {
        return $this->httpClient;
    }

    public function setHttpClient(Client $client): self
    {
        $this->httpClient = $client;
        return $this;
    }

    private function responseToProxy(ResponseInterface $response): Proxy
    {
        /**
         * @var array{
         *     name: string,
         *     enabled: bool,
         *     upstream: string,
         *     listen: string,
         *     toxics: array{
         *          name: string,
         *          type: string,
         *          stream: string,
         *          toxicity: float,
         *          attributes: array<string, mixed>
         *      }[]
         * } $responseBody
         */
        $responseBody = (array) json_decode((string) $response->getBody(), true);
        return $this->contentsToProxy($responseBody);
    }

    /**
     * @param array{
     *     name: string,
     *     enabled: bool,
     *     upstream: string,
     *     listen: string,
     *     toxics: array{
     *          name: string,
     *          type: string,
     *          stream: string,
     *          toxicity: float,
     *          attributes: array<string, mixed>
     *      }[]
     * } $contents
     */
    private function contentsToProxy(array $contents): Proxy
    {
        $proxy = new Proxy($this, $contents['name'], $contents['toxics']);
        $proxy->setEnabled($contents['enabled'])
            ->setUpstream($contents['upstream'])
            ->setListen($contents['listen']);

        return $proxy;
    }

    /**
     * @throws ProxyExistsException|UnexpectedStatusCodeException
     */
    public function create(string $name, string $upstream, ?string $listen = null): Proxy
    {
        $route = $this->createProxyRoute();
        $response = $this->httpClient->request(
            $route['method'],
            $route['uri'],
            [
                'body' => json_encode(['name' => $name, 'upstream' => $upstream, 'listen' => $listen]),
            ],
        );
        switch ($response->getStatusCode()) {
            case StatusCodes::CREATED->value:
                return $this->responseToProxy($response);
            case StatusCodes::CONFLICT->value:
                throw new ProxyExistsException((string) $response->getBody());
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
     * @return Proxy[]
     * @throws InvalidProxyException|UnexpectedStatusCodeException
     */
    public function populate(array $proxyBodies): array
    {
        $route = $this->populateRoute();
        $response = $this->httpClient->request(
            $route['method'],
            $route['uri'],
            [
            'body' => json_encode($proxyBodies),
            ],
        );
        switch ($response->getStatusCode()) {
            case StatusCodes::CREATED->value:
                /**
                 * @var array{
                 *     proxies: array{
                 *          name: string,
                 *          enabled: bool,
                 *          upstream: string,
                 *          listen: string,
                 *          toxics: array{
                 *              name: string,
                 *              type: string,
                 *              stream: string,
                 *              toxicity: float,
                 *              attributes: array<string, mixed>
                 *          }[]
                 *      }[]
                 * } $contents
                 */
                $contents = json_decode((string) $response->getBody(), true);

                return array_map(
                    fn(array $contents) => $this->contentsToProxy($contents),
                    $contents['proxies'],
                );
            case StatusCodes::BAD_REQUEST->value:
                throw new InvalidProxyException((string) $response->getBody());
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
    public function get(string $name): ?Proxy
    {
        $route = $this->getProxyRoute($name);
        $response = $this->httpClient->request((string) $route['method'], (string) $route['uri']);
        switch ($response->getStatusCode()) {
            case StatusCodes::OK->value:
                return $this->responseToProxy($response);
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
     * @return Proxy[]
     * @throws UnexpectedStatusCodeException
     */
    public function getAll(): array
    {
        $route = $this->getProxiesRoute();
        $response = $this->httpClient->request($route['method'], $route['uri']);
        switch ($response->getStatusCode()) {
            case StatusCodes::OK->value:
                /**
                 * @var array{
                 *     name: string,
                 *     enabled: bool,
                 *     upstream: string,
                 *     listen: string,
                 *     toxics: array{
                 *          name: string,
                 *          type: string,
                 *          stream: string,
                 *          toxicity: float,
                 *          attributes: array<string, mixed>
                 *      }[]
                 * }[] $body
                 */
                $body = (array) json_decode((string) $response->getBody(), true);

                return array_map(
                    fn(array $contents) => $this->contentsToProxy($contents),
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
     * @throws NotFoundException|UnexpectedStatusCodeException
     */
    public function delete(Proxy $proxy): void
    {
        $route = $this->deleteProxyRoute($proxy);
        $response = $this->httpClient->request((string) $route['method'], (string) $route['uri']);
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

    /**
     * @throws NotFoundException|UnexpectedStatusCodeException
     */
    public function update(Proxy $proxy): Proxy
    {
        $route = $this->updateProxyRoute($proxy);
        $response = $this->httpClient->request(
            (string) $route['method'],
            (string) $route['uri'],
            [
                'body' => json_encode($proxy),
            ],
        );
        switch ($response->getStatusCode()) {
            case StatusCodes::OK->value:
                return $this->responseToProxy($response);
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
     * @throws UnexpectedStatusCodeException
     */
    public function reset(): void
    {
        $route = $this->resetRoute();
        $response = $this->httpClient->request($route['method'], $route['uri']);
        switch ($response->getStatusCode()) {
            case StatusCodes::NO_CONTENT->value:
                return;
            default:
                throw new UnexpectedStatusCodeException(
                    sprintf('Unexpected status code: %s', $response->getStatusCode()),
                );
        }
    }

    /**
     * @throws UnexpectedStatusCodeException
     */
    public function version(): string
    {
        $route = $this->versionRoute();
        $response = $this->httpClient->request($route['method'], $route['uri']);
        switch ($response->getStatusCode()) {
            case StatusCodes::OK->value:
                return (string) $response->getBody();
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
}
