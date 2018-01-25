Toxiproxy PHP Client
====================

[![Build status](https://travis-ci.org/ihsw/toxiproxy-php-client.svg?branch=master)](https://travis-ci.org/ihsw/toxiproxy-php-client)
[![Coverage Status](https://coveralls.io/repos/github/ihsw/toxiproxy-php-client/badge.svg?branch=master)](https://coveralls.io/github/ihsw/toxiproxy-php-client?branch=master)

[Toxiproxy](https://github.com/shopify/toxiproxy) makes it easy and trivial to test network conditions, for example low-bandwidth and high-latency situations. `toxiproxy-php-client` includes everything needed to get started with configuring Toxiproxy upstream connection and listen endpoints.

*Note: `toxiproxy-php-client` is currently compatible with `toxiproxy-2.0+`.*

Installing via Composer
-----------------------

The recommended way to install `toxiproxy-php-client` is through [Composer](http://getcomposer.org/).

Once that is installed and you have added `ihsw/toxiproxy-php-client` to your `composer.json` configuration, you can require the autoloader and start using the library.

Here is an example for creating a proxy that limits a Redis connection to 1000KB/s.

```php
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

```

Documentation
-------------

Additional examples can be found in the `examples` directory for expected usage.
