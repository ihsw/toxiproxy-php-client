<?php namespace Ihsw\Toxiproxy;

use GuzzleHttp\Client as HttpClient,
    GuzzleHttp\Exception as HttpException;
use Ihsw\Toxiproxy\Exception;

class Client
{
    private $response;

    public function __construct($upstream, $name)
    {
        $httpClient = new HttpClient(["base_url" => "http://127.0.0.1:8474"]);
        try
        {
            $this->response = $httpClient->post("/proxies", [
                "body" => json_encode([
                    "upstream" => $upstream,
                    "name" => $name
                ])
            ]);
        }
        catch (HttpException\ClientException $e)
        {
            if ($e->getResponse()->getStatusCode() === 409) {
                throw new Exception\ProxyExists($e->getResponse()->getBody());
            }
        }
    }

    public function getResponse()
    {
        return $this->response;
    }
}