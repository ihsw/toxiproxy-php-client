<?php

require("./vendor/autoload.php");

use React\EventLoop\Factory as EventLoopFactory,
    React\Dns\Resolver\Factory as DnsResolverFactory,
    React\SocketClient\Connector as ClientConnector,
    React\SocketClient\ConnectionException,
    React\Stream\Stream;

$canConnect = function($ip, $port){
    // misc
    $loop = EventLoopFactory::create();
    $dnsResolverFactory = new DnsResolverFactory();
    $dns = $dnsResolverFactory->createCached("8.8.8.8", $loop); // dunno why dns is required for this shit
    $connector = new ClientConnector($loop, $dns);

    // socket loop
    $promise = $connector->create($ip, $port)->then(function (Stream $stream) use ($loop) {
        $stream->close();
        return true;
    }, function(ConnectionException $e) use($loop){
        return false;
    });

    // starting it up
    $loop->run();

    // catching the output
    $out = null;
    $promise->done(function($v) use(&$out){
        $out = $v;
    });

    return $out;
};

var_dump($canConnect("127.0.0.1", 43434));