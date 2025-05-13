<?php

namespace Ihsw\Toxiproxy;

use JsonSerializable;

class Toxic implements JsonSerializable
{
    private Proxy $proxy;
    private string $name;
    private string $type;
    private string $stream;
    private float $toxicity;
    private array $attributes;

    public function __construct(Proxy $proxy, string $name, string $type, string $stream)
    {
        $this->proxy = $proxy;
        $this->name = $name;
        $this->type = $type;
        $this->stream = $stream;
        $this->toxicity = 1.0;
        $this->attributes = [];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getStream(): string
    {
        return $this->stream;
    }

    public function getToxicity(): float
    {
        return $this->toxicity;
    }

    public function setToxicity(float $toxicity): self
    {
        $this->toxicity = $toxicity;
        return $this;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function setAttributes(array $attributes): self
    {
        $this->attributes = $attributes;
        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'stream' => $this->stream,
            'type' => $this->type,
            'toxicity' => $this->toxicity,
            'attributes' => $this->attributes,
        ];
    }
}
