<?php

require("./vendor/autoload.php");

use GuzzleHttp\Client as HttpClient;
use Ihsw\Toxiproxy\Toxiproxy;

$toxiproxy = new Toxiproxy(new HttpClient(["base_url" => "http://127.0.0.1:8474"]));
$proxies = array_filter($toxiproxy->all(), function($proxy) {
    return strlen($proxy->getName()) > 0;
});
foreach ($proxies as $proxy) {
    $toxiproxy->delete($proxy);
}
