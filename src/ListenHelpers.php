<?php

namespace Ihsw\Toxiproxy;

trait ListenHelpers
{
    public function getListenIp()
    {
        $ip = implode(":", explode(":", $this->listen, -1));
        if (substr($ip, 0, 1) === "[") {
            $ip = substr($ip, 1, -1);
        }
        return $ip;
    }

    public function getListenPort()
    {
        $ip = $this->getListenIp();
        $start = substr($this->listen, 0, 1) === "[" ? 3 : 1;
        return substr($this->listen, $start + strlen($ip));
    }
}
