<?php

require("./vendor/autoload.php");

use Ihsw\Toxiproxy\Toxiproxy;

$toxiproxy = new Toxiproxy("http://127.0.0.1:8474");
$proxy = $toxiproxy->create("ihsw_example_redis_master", "127.0.0.1:8474", "127.0.0.1:53535");
$proxy->updateDownstream("latency", ["enabled" => true, "jitter" => 1000]);