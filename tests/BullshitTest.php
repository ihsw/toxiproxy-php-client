<?php

use GuzzleHttp\Subscriber\Mock,
    GuzzleHttp\Client,
    GuzzleHttp\Stream\Stream,
    GuzzleHttp\Message\Response;

class BullshitTest extends \PHPUnit_Framework_TestCase
{
    public function testBullshit()
    {
        $client = new Client(["base_url" => "http://www.test.com"]);

        $mock = new Mock([
            new Response(200, [], Stream::factory("Hello, world!"))
        ]);
        $client->getEmitter()->attach($mock);

        $response = $client->get("/");
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($response->getBody(), "Hello, world!");
    }
}