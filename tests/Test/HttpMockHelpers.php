<?php

declare(strict_types=1);

namespace Ihsw\Toxiproxy\Tests\Test;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler as HttpMockHandler;
use GuzzleHttp\HandlerStack as HttpHandlerStack;
use GuzzleHttp\Psr7\Response as HttpResponse;

trait HttpMockHelpers
{
    /**
     * @param array<int,mixed> $responses
     */
    protected static function mockHttpClientFactory(?array $responses): HttpClient
    {
        $mock = new HttpMockHandler($responses);
        $handler = HttpHandlerStack::create($mock);
        return new HttpClient(
            [
                'handler' => $handler,
                'http_errors' => false,
            ],
        );
    }

    /**
     * @param string[][] $headers
     */
    protected static function httpResponseFactory(int $statusCode, string $body, array $headers = []): HttpResponse
    {
        return new HttpResponse($statusCode, $headers, $body);
    }
}
