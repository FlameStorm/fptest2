<?php

use Payment\ExtCurrency;
use Withdraw\WithdrawalResult;

abstract class PaymentSystem
{
    const OT_STRING = 1;
    const OT_INTEGER = 2;
    const OT_TEXT = 3;
    const OT_BOOL = 4;

    const KEY_PHONE = 'phone';
    const KEY_RECEIPT_ADDR = 'receipt_addr';

    const PHRASE_INPUT_DISABLED = 'Ввод средств временно невозможен.';
    const PHRASE_OUTPUT_DISABLED = 'Вывод средств временно невозможен.';

    const OFFSET_STEP = 10000000;

    const AMOUNT_MIN = 0.01;

    protected $input = false;
    protected $output = false;

    abstract public function getInputAccount();

    abstract public function withdraw(ExtCurrency $extCurrency, string $wallet, $amount, $comment, $withdrawalId): WithdrawalResult;

    public function isActiveInput()
    {
        return (bool)$this->input;
    }

    public function isActiveOutput()
    {
        return (bool)$this->output;
    }

    public function isPhoneRequired()
    {
        return false;
    }

    public function isReceiptAddrRequired()
    {
        return false;
    }

    public function canGetBalances(): bool
    {
        return false;
    }

    public function getBalances(): array
    {
        throw new LE(Phrases::ERROR_UNKNOWN);
    }

    protected function requireActiveInput()
    {
        if (!$this->isActiveInput()) {
            throw new LE(self::PHRASE_INPUT_DISABLED);
        }
    }

    protected function requireActiveOutput()
    {
        if (!$this->isActiveOutput()) {
            throw new LE(self::PHRASE_OUTPUT_DISABLED);
        }
    }
}