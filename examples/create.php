<?php

require("./vendor/autoload.php");

use GuzzleHttp\Client as HttpClient;
use Ihsw\Toxiproxy\Toxiproxy;

$toxiproxy = new Toxiproxy(new HttpClient(["base_url" => "http://127.0.0.1:8474"]));
$name = "ihsw_example_redis_master";
$proxy = $toxiproxy->get($name);
if (is_null($proxy)) {
	$proxy = $toxiproxy->create($name, "127.0.0.1:6379", "127.0.0.1:43434");
}