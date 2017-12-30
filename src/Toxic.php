<?php

namespace Ihsw\Toxiproxy;

class Toxic
{
    const LATENCY = "latency";
    const DOWN = "down";
    const BANDWIDTH = "bandwidth";
    const SLOW_CLOSE = "slow_close";
    const TIMEOUT = "timeout";
    const SLICER = "slicer";

    /**
     * @var Proxy
     */
    private $proxy;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $type;

    /**
     * @var float
     */
    private $toxicity;

    /**
     * @var array
     */
    private $attributes;

    /**
     * Toxic constructor.
     * @param Proxy $proxy
     */
    public function __construct(Proxy $proxy)
    {
        $this->proxy = $proxy;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return float
     */
    public function getToxicity()
    {
        return $this->toxicity;
    }

    /**
     * @param float $toxicity
     */
    public function setToxicity($toxicity)
    {
        $this->toxicity = $toxicity;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @param array $attributes
     */
    public function setAttributes($attributes)
    {
        $this->attributes = $attributes;
    }
}
