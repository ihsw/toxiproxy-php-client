<?php

namespace Ihsw\Toxiproxy\Test;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler as HttpMockHandler;
use GuzzleHttp\HandlerStack as HttpHandlerStack;
use GuzzleHttp\Psr7 as HttpPsr7;
use GuzzleHttp\Psr7\Response as HttpResponse;

trait HttpMockHelpers
{
    protected static function mockHttpClientFactory(array $responses)
    {
        $mock = new HttpMockHandler($responses);
        $handler = HttpHandlerStack::create($mock);
        $client = new HttpClient([
            "handler" => $handler,
            "http_errors" => false
        ]);
        return $client;
    }

    protected static function httpResponseFactory($statusCode, $body, array $headers = [])
    {
        return new HttpResponse($statusCode, $headers, HttpPsr7\stream_for($body));
    }
}
