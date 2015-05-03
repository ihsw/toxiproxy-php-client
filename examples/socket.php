<?php

require("./vendor/autoload.php");

$loop = React\EventLoop\Factory::create();

$dnsResolverFactory = new React\Dns\Resolver\Factory();
$dns = $dnsResolverFactory->createCached("8.8.8.8", $loop);
$connector = new React\SocketClient\Connector($loop, $dns);
$connector->create("127.0.0.1", 43434)->then(function (React\Stream\Stream $stream) use ($loop) {
    $i = 0;
    $loop->addPeriodicTimer(1, function(React\EventLoop\Timer\Timer $timer) use (&$i, $loop, $stream) {
    	$i++;
    	$command = sprintf("SET derp:%s %s", $i, $i). "\n";
    	echo $command;
        $stream->write($command . PHP_EOL);

        if ($i >= 15) {
            $loop->cancelTimer($timer);
            $stream->close();
        }
    });
    $stream->on("data", function ($data) {
        echo $data;
    });
});

$loop->run();