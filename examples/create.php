<?php

require("../vendor/autoload.php");

use Ihsw\Toxiproxy\Toxiproxy;

$toxiproxy = new Toxiproxy();
$proxy = $toxiproxy->create("ihsw_test_redis_master", "localhost:6379", "localhost:43434");