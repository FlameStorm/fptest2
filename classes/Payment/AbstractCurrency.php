<?php

namespace Payment;

abstract class AbstractCurrency
{
    private $id;
    private $name;
    private $unit;

    public function __construct(string $id, string $name, string $unit)
    {
        $this->id = $id;
        $this->name = $name;
        $this->unit = $unit;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }
}