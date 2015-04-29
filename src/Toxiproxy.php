<?php namespace Ihsw\Toxiproxy;

use GuzzleHttp\Client as HttpClient,
    GuzzleHttp\Exception\ClientException as HttpClientException;
use Ihsw\Toxiproxy\Exception\ProxyExistsException;

class Toxiproxy
{
    private $httpClient;

    public function __construct()
    {
        $this->httpClient = new HttpClient(["base_url" => "http://127.0.0.1:8474"]);
    }

    /**
     * @return HttpClient
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }

    /**
     * @param string
     */
    public function create($proxy, $upstream)
    {
        return $this->httpClient->post("/proxies", [
            "body" => json_encode(["proxy" => $proxy, "upstream" => $upstream])
        ]);
    }

    public function findAll()
    {
        return json_decode($this->httpClient->get("/proxies")->getBody(), true);
    }

    /**
     * @param string
     * @return uh
     */
    public function delete($proxy)
    {
        return $this->httpClient->delete(sprintf("/proxies/%s", $proxy));
    }
}