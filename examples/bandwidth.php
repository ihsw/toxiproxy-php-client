<?php

require("./vendor/autoload.php");

use Ihsw\Toxiproxy\Toxiproxy;
use Ihsw\Toxiproxy\ToxicTypes;

$toxiproxy = new Toxiproxy("http://toxiproxy:8474");
$proxy = $toxiproxy->create("ihsw_example_redis_master", "127.0.0.1:6379");
$toxic = $proxy->create(ToxicTypes::BANDWIDTH, "upstream", 1.0, [
    "rate" => 1000
]);
printf(
    "Listening on IP %s and port %s on behalf of 6379, with a connection that's limited to 1000KB/s\n",
    $proxy->getListenIp(),
    $proxy->getListenPort()
);

$toxiproxy->delete($proxy);
