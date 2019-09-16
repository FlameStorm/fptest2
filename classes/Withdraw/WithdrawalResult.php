<?php

namespace Withdraw;

use Phrases;

class WithdrawalResult
{
    private $success = false;
    private $failure = false;
    private $failureReason = '';
    private $failureException;
    private $account = '';
    private $externalId = '';
    private $fee;

    public function setSuccess()
    {
        $this->success = true;
    }

    public function isSuccess()
    {
        return $this->success;
    }

    private function hideSecretInfo($s)
    {
        if (!is_string($s) || !$s) {
            return '';
        }
        $s = preg_replace('/\d+(\.\d+)+/', '*', $s); // ip

        return $s;
    }

    public function setFailure($reason = '', $exception = null)
    {
        $this->failure = true;
        $this->failureReason = $this->hideSecretInfo($reason) ?: Phrases::ERROR_UNKNOWN;
        $this->failureException = $exception;
    }

    public function isFailure()
    {
        return $this->failure;
    }

    public function getFailureReason()
    {
        return $this->failureReason;
    }

    public function getFailureException()
    {
        return $this->failureException;
    }

    public function setAccount($account)
    {
        $this->account = $account;
    }

    public function getAccount()
    {
        return $this->account;
    }

    public function setExternalId($externalId)
    {
        $this->externalId = $externalId;
    }

    public function getExternalId()
    {
        return $this->externalId;
    }

    public function setFee(?float $fee)
    {
        $this->fee = $fee;
    }

    public function getFee(): ?float
    {
        return $this->fee;
    }

    public function __toString()
    {
        $data = [
            'success' => $this->success,
            'failure' => $this->failure,
        ];
        $a = [
            'account' => $this->account,
            'externalId' => $this->externalId,
            'fee' => $this->fee,
        ];
        $data += array_filter($a);
        $a = [];
        foreach ($data as $key => $value) {
            $a[] = $key . ' = ' . (is_bool($value) ? (int)$value : $value);
        }
        $s = 'Result: ' . implode(', ', $a);
        if ($this->failureReason) {
            $s .= "\n" . $this->failureReason;
        }
        if ($this->failureException) {
            $s .= "\n" . (string)getPrimaryException($this->failureException);
        }

        return $s;
    }
}