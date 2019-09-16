<?php

namespace Payment\QiwiWalletApi;

class QiwiTransaction
{
    private $id = '';
    private $commission = 0;

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function setCommission(float $commission): self
    {
        $this->commission = $commission;

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCommission(): float
    {
        return $this->commission;
    }
}