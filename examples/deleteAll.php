<?php

require("./vendor/autoload.php");

use Ihsw\Toxiproxy\Toxiproxy;

$toxiproxy = new Toxiproxy();
$proxies = array_filter($toxiproxy->all(), function($proxy){
    return strlen($proxy["name"]) > 0;
});
foreach ($proxies as $proxy) {
    $toxiproxy->delete($proxy);
}