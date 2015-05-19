Toxiproxy PHP Client
====================

[![Build status](https://travis-ci.org/ihsw/toxiproxy-php-client.svg?branch=master)](https://travis-ci.org/ihsw/toxiproxy-php-client)

[Toxiproxy](https://github.com/shopify/toxiproxy) makes it easy and trivial to test network conditions, for example low-bandwidth and high-latency situations. `toxiproxy-php-client` includes everything needed to get started with configuring Toxiproxy upstream connection and listen endpoints.

Installing via Composer
-----------------------

The recommended way to install `toxiproxy-php-client` is through [Composer](http://getcomposer.org/).

Once that is installed and you have added `ihsw/toxiproxy-php-bundle` to your `composer.json` configuration, you can require the autoloader and start using the library.

Here is an example for creating a proxy that limits a Redis connection to 1000KB/s.

```php
<?php

require("./vendor/autoload.php");

use GuzzleHttp\Client as HttpClient;
use Ihsw\Toxiproxy\Toxiproxy;

// hooking up to toxiproxy and starting a bandwidth proxy
$toxiproxy = new Toxiproxy(new HttpClient(["base_url" => "http://127.0.0.1:8474"]));
$proxy = $toxiproxy->create("ihsw_example_redis_master", "127.0.0.1:6379");
$proxy->updateDownstream("bandwidth", ["enabled" => true, "rate" => 1000]);
printf(
	"Listening on IP %s and port %s on behalf of 6379, limited to 1000KB/s\n",
	$proxy->getListenIp(),
	$proxy->getListenPort()
);
```

Documentation
-------------

Additional examples can be found in the `examples` directory for expected usage.