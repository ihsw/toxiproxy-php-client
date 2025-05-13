<?php

namespace Ihsw\Toxiproxy;

trait ListenHelpers
{
    public function getListenIp(): string
    {
        $ip = implode(":", explode(":", $this->listen, -1));
        if (str_starts_with($ip, "[")) {
            $ip = substr($ip, 1, -1);
        }
        return $ip;
    }

    public function getListenPort(): string
    {
        $ip = $this->getListenIp();
        $start = str_starts_with($this->listen, "[") ? 3 : 1;
        return substr($this->listen, $start + strlen($ip));
    }
}
