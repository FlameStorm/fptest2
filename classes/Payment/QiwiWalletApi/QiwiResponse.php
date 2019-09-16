<?php

namespace Payment\QiwiWalletApi;

class QiwiResponse
{
    private $status;
    private $data;

    public function __construct(int $status, $data)
    {
        $this->status = $status;
        $this->data = $data;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getError(): ?string
    {
        $status = $this->getStatus();
        if ($status == 200) {
            return null;
        }

        $data = $this->getData();
        if ($status == 0) {
            $message = $data;
        } else {
            $message = '';
            foreach (['userMessage', 'message'] as $key) {
                $message = getStrFromArray($data, $key);
                if ($message) {
                    break;
                }
            }
        }

        return $message ?: "Request failed (status {$status}).";
    }
}