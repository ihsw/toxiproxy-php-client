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
     */
    public function __construct(Proxy $proxy, $name)
    {
        $this->proxy = $proxy;
        $this->name = $name;
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
    public function getStream()
    {
        return $this->stream;
    }

    /**
     * @param string $stream
     * @return $this
     */
    public function setStream($stream)
    {
        $this->stream = $stream;
        return $this;
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
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
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
