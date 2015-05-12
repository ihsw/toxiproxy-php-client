<?php

require("./vendor/autoload.php");

use GuzzleHttp\Client as HttpClient;
use Ihsw\Toxiproxy\Toxiproxy;

$toxiproxy = new Toxiproxy(new HttpClient(["base_url" => "http://127.0.0.1:8474"]));
$proxy = $toxiproxy->create("ihsw_example_redis_master", "127.0.0.1:6379");
$proxy->updateDownstream("latency", ["enabled" => true, "latency" => 100, "jitter" => 100]);

$listen = $proxy->getListen();
$ip = implode(":", explode(":", $proxy->getListen(), -1));
$port = substr($listen, 1 + strlen($ip));
printf(
	"Listening on IP %s and port %s on behalf of 6379, with latency between 100ms and 200ms, randomly distributed\n",
	$ip,
	$port
);