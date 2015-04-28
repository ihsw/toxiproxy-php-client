<?php namespace Ihsw\Toxiproxy;

use GuzzleHttp\Client;

class Toxiproxy
{
    private $client;

    public function __construct()
    {
        $this->client = new Client("http://127.0.0.1:8474/");
    }

    public function hasDerp($derp = true)
    {
        return $derp;
    }
}