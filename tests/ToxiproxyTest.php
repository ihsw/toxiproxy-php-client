<?php

use GuzzleHttp\Client as HttpClient;
use Ihsw\Toxiproxy\Toxiproxy,
	Ihsw\Toxiproxy\Client;

class ToxiproxyTest extends \PHPUnit_Framework_TestCase
{
    public function testGetHttpClient($callback = null)
    {
        $toxiproxy = new Toxiproxy();
        $this->assertTrue($toxiproxy->getHttpClient() instanceof HttpClient, "Toxiproxy http-client was not an instance of HttpClient");

        if (!is_null($callback))
        {
        	$callback($toxiproxy);
        }
    }

    public function testFindAll()
    {
    	$this->testGetHttpClient(function(Toxiproxy $toxiproxy){
    		$proxies = array_filter($toxiproxy->findAll(), function($proxy){
    			return strlen($proxy["name"]) > 0;
    		});
    		foreach ($proxies as $proxy)
    		{
    			$toxiproxy->delete($proxy["name"]);
    		}
    	});
    }
}