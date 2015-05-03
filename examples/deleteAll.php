<?php

require("./vendor/autoload.php");

use Ihsw\Toxiproxy\Toxiproxy;

$toxiproxy = new Toxiproxy("http://127.0.0.1:8474");
$proxies = array_filter($toxiproxy->all(), function($proxy) {
    return strlen($proxy["name"]) > 0;
});
foreach ($proxies as $proxy) {
    $toxiproxy->delete($proxy);
}