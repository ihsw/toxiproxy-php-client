<?php

require("./vendor/autoload.php");

use Ihsw\Toxiproxy\Toxiproxy;

$toxiproxy = new Toxiproxy();
$proxy = $toxiproxy->create("ihsw_example_redis_master", "localhost:6379", "localhost:43434");
$proxy->update("jitter", "downstream", ["jitter" => 100]);