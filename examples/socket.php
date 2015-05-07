<?php

require("./vendor/autoload.php");

use React\EventLoop\Factory as EventLoopFactory,
    React\Dns\Resolver\Factory as DnsResolverFactory,
    React\Socket\Server,
    React\SocketClient\Connector,
    React\SocketClient\ConnectionException,
    React\Stream\Stream;

// misc
$ip = "127.0.0.1";
$port = 44445;

// server setup
$serverLoop = EventLoopFactory::create();
$server = new Server($serverLoop);
$server->listen($port);
$serverLoop->run();

// client setup
$clientLoop = EventLoopFactory::create();
$dnsResolverFactory = new DnsResolverFactory();
$dns = $dnsResolverFactory->createCached("8.8.8.8", $clientLoop); // dunno why dns is required for this shit
$connector = new Connector($clientLoop, $dns);
$promise = $connector->create($ip, $port)->then(function (Stream $stream) {
    $stream->close();
    return true;
}, function(ConnectionException $e) {
    return false;
});
$clientLoop->run();

// catching the output
$out = null;
$promise->done(function($v) use(&$out) {
    $out = $v;
});

// cleanup
$server->shutdown();

var_dump($out);