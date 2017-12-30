<?php

namespace Ihsw\Toxiproxy;

class Proxy
{
    const UPSTREAM = "upstream";
    const DOWNSTREAM = "downstream";

    /**
     * @var Toxiproxy
     */
    private $toxiproxy;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $listen;

    /**
     * @var string
     */
    private $upstream;

    /**
     * @var bool
     */
    private $enabled;

    /**
     * @var Toxic[]
     */
    private $toxics;

    use ListenHelpers;

    /**
     * Proxy constructor.
     * @param Toxiproxy $toxiproxy
     */
    public function __construct(Toxiproxy $toxiproxy)
    {
        $this->toxiproxy = $toxiproxy;
        $this->toxics = [];
    }

    /**
     * @param $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $listen
     * @return $this
     */
    public function setListen($listen)
    {
        $this->listen = $listen;
        return $this;
    }

    /**
     * @return string
     */
    public function getListen()
    {
        return $this->listen;
    }

    /**
     * @return string
     */
    public function getUpstream()
    {
        return $this->upstream;
    }

    /**
     * @param string $upstream
     * @return $this
     */
    public function setUpstream($upstream)
    {
        $this->upstream = $upstream;
        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     * @return $this
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
        return $this;
    }
}
