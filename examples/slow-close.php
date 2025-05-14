<?php

declare(strict_types=1);

require('./vendor/autoload.php');

use Ihsw\Toxiproxy\StreamDirections;
use Ihsw\Toxiproxy\ToxicTypes;
use Ihsw\Toxiproxy\Toxiproxy;

$toxiproxy = new Toxiproxy('http://toxiproxy:8474');
$proxy = $toxiproxy->create('ihsw_example_redis_master', '127.0.0.1:6379');
$toxic = $proxy->create(ToxicTypes::SLOW_CLOSE->value, StreamDirections::UPSTREAM->value, 1.0, [
    'delay' => 1000,
]);
printf(
    "Listening on IP %s and port %s on behalf of 6379, with a connection that takes 1000ms to close\n",
    $proxy->getListenIp(),
    $proxy->getListenPort(),
);

$toxiproxy->delete($proxy);
