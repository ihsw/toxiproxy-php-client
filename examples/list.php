<?php

require("./vendor/autoload.php");

use GuzzleHttp\Client as HttpClient;
use Ihsw\Toxiproxy\Toxiproxy;

// hooking up to toxiproxy and starting a bandwidth proxy
$toxiproxy = new Toxiproxy(new HttpClient(["base_url" => "http://127.0.0.1:8474"]));
$proxies = $toxiproxy->all();
if (count($proxies) === 0) {
	printf("no proxies found\n");
	return;
}

foreach ($proxies as $proxy) {
	printf("%s\n", $proxy->getName());
}