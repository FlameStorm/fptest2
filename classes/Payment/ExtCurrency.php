<?php

namespace Payment;

class ExtCurrency extends AbstractCurrency
{
    private $walletName;

    public function __construct(string $id, string $name, string $unit, string $walletName)
    {
        parent::__construct($id, $name, $unit);

        $this->walletName = $walletName;
    }

    public function getWalletName(): string
    {
        return $this->walletName;
    }
}
