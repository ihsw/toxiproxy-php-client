<?php

require("./vendor/autoload.php");

use GuzzleHttp\Client as HttpClient;
use Ihsw\Toxiproxy\Toxiproxy;

$toxiproxy = new Toxiproxy(new HttpClient(["base_url" => "http://127.0.0.1:8474"]));
$proxy = $toxiproxy->create("ihsw_example_redis_master", "127.0.0.1:6379", "127.0.0.1:42424");
printf(
	"Listening on IP %s and port %s on behalf of 6379\n",
	$proxy->getListenIp(),
	$proxy->getListenPort()
);