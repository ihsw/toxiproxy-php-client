<?php

namespace Ihsw\Toxiproxy;

class Toxic implements \JsonSerializable
{
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
     * @var string
     */
    private $stream;

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
     * @param string $name
     * @param string $type
     * @param string $stream
     */
    public function __construct(Proxy $proxy, $name, $type, $stream)
    {
        $this->proxy = $proxy;
        $this->name = $name;
        $this->type= $type;
        $this->stream= $stream;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getStream()
    {
        return $this->stream;
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
     * @return $this
     */
    public function setToxicity($toxicity)
    {
        $this->toxicity = $toxicity;
        return $this;
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
     * @return $this
     */
    public function setAttributes($attributes)
    {
        $this->attributes = $attributes;
        return $this;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            "name" => $this->name,
            "stream" => $this->stream,
            "type" => $this->type,
            "toxicity" => $this->toxicity,
            "attributes" => $this->attributes
        ];
    }
}
