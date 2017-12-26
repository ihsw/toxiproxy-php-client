<?php

require("./vendor/autoload.php");

use GuzzleHttp\Client as HttpClient;
use Ihsw\Toxiproxy\Toxiproxy;

// hooking up to toxiproxy and starting a slow-close proxy
$toxiproxy = new Toxiproxy(new HttpClient(["base_url" => "http://127.0.0.1:8474"]));
$proxy = $toxiproxy->create("ihsw_example_redis_master", "127.0.0.1:6379");
$proxy->updateDownstream("timeout", ["enabled" => true, "timeout" => 5*1000]);
printf(
    "Listening on IP %s and port %s on behalf of 6379, with a connection that does nothing but timeout after 5000ms\n",
    $proxy->getListenIp(),
    $proxy->getListenPort()
);
