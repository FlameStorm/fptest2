<?php

namespace Payment\QiwiWalletApi;

use Phrases;

class QiwiWalletApi
{
    const TXN_TYPE_IN = 'IN';
    const TXN_TYPE_OUT = 'OUT';

    const RM_GET = 'GET';
    const RM_POST = 'POST';

    const AMOUNT_MAX = 1000000;

    private $token = '';
    private $proxyHost, $proxyPort, $proxyType;
    private $curl;
    private $host = 'https://edge.qiwi.com';

    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    public function setProxy(string $host, int $port, int $type): void
    {
        $this->proxyHost = $host;
        $this->proxyPort = $port;
        $this->proxyType = $type;
    }

    /**
     * @return resource
     * @throws QiwiException
     */
    private function getCurl()
    {
        if (!is_resource($this->curl)) {
            $c = curl_init();
            if (!$c) {
                throw new QiwiException('Problem with curl_init.');
            }

            curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 30);
            curl_setopt($c, CURLOPT_TIMEOUT, 30);
            curl_setopt($c, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($c, CURLOPT_ENCODING, '');
            curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);

            if ($this->proxyHost) {
                curl_setopt($c, CURLOPT_PROXY, $this->proxyHost);
                curl_setopt($c, CURLOPT_PROXYPORT, $this->proxyPort);
                curl_setopt($c, CURLOPT_PROXYTYPE, $this->proxyType);

                if (in_array($this->proxyHost, ['127.0.0.1', 'localhost'])) {
                    curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 0);
                }
            }

            $this->curl = $c;
        }

        return $this->curl;
    }

    /**
     * @param string $url
     * @param array $options
     * @return QiwiResponse
     * @throws QiwiException
     */
    private function request(string $url, array $options = []): QiwiResponse
    {
        $url = trim($url);
        if (!$url) {
            throw new QiwiException('Invalid url.');
        }
        if ($url[0] == '/') {
            $url = $this->host . $url;
        }

        $method = getStrFromArray($options, 'method', self::RM_GET);
        if (!in_array($method, [self::RM_GET, self::RM_POST])) {
            throw new QiwiException('Invalid method.');
        }
        $isGet = $method == self::RM_GET;
        $isPost = $method == self::RM_POST;

        $fields = getArrayFromArray($options, 'fields');

        if ($isGet && $fields) {
            $url .= '?' . http_build_query($fields);
        }

        $c = $this->getCurl();

        curl_setopt($c, CURLOPT_POST, $isPost);
        if ($isPost) {
            curl_setopt($c, CURLOPT_POSTFIELDS, json_encode($fields));
        }

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
        if ($this->token) {
            $headers['Authorization'] = 'Bearer ' . $this->token;
        }
        $a = [];
        foreach ($headers as $key => $value) {
            $a[] = $key . ': ' . $value;
        }
        curl_setopt($c, CURLOPT_HTTPHEADER, $a);

        curl_setopt($c, CURLOPT_URL, $url);

        $s = curl_exec($c);
        if ($s === false) {
            $status = 0;
            $data = curl_error($c) ?: 'Problem with curl_exec.';
        } else {
            $status = (int)curl_getinfo($c, CURLINFO_HTTP_CODE);
            $data = json_decode($s, true);
        }

        return new QiwiResponse($status, $data);
    }

    /**
     * @param string $url
     * @param array $fields
     * @return QiwiResponse
     * @throws QiwiException
     */
    private function requestGet(string $url, array $fields = []): QiwiResponse
    {
        return $this->request($url, ['method' => self::RM_GET, 'fields' => $fields]);
    }

    /**
     * @param string $url
     * @param array $fields
     * @return QiwiResponse
     * @throws QiwiException
     */
    private function requestPost(string $url, array $fields = []): QiwiResponse
    {
        return $this->request($url, ['method' => self::RM_POST, 'fields' => $fields]);
    }

    /**
     * @throws QiwiException
     */
    private function requireToken(): void
    {
        if (!$this->token) {
            throw new QiwiException('Token required.');
        }
    }

    /**
     * @param QiwiResponse $response
     * @throws QiwiException
     */
    private function requireSuccess(QiwiResponse $response): void
    {
        $error = $response->getError();
        if (!is_null($error)) {
            throw new QiwiException($error);
        }
    }

    /**
     * @param $phone
     * @return string
     * @throws QiwiException
     */
    public function normalizePhoneOrFail($phone): string
    {
        $phone = normalizePhone($phone);
        if (!$phone) {
            throw new QiwiException(Phrases::PHONE_INVALID);
        }

        return $phone;
    }

    /**
     * @param $amount
     * @return float
     * @throws QiwiException
     */
    public function normalizeAmountOrFail($amount): float
    {
        $amount = floorEx($amount, 2);
        if ($amount < 0.01 || $amount > self::AMOUNT_MAX) {
            throw new QiwiException(Phrases::SUM_INVALID);
        }

        return $amount;
    }

    /**
     * @param $id
     * @return string
     * @throws QiwiException
     */
    public function normalizeTxnIdOrFail($id): string
    {
        $id = is_string($id) ? trim($id) : '';
        if (!preg_match('/^\d{1,20}\z/', $id)) {
            throw new QiwiException('Invalid transaction id.');
        }

        return $id;
    }

    /**
     * @param $type
     * @return string
     * @throws QiwiException
     */
    public function normalizeTxnTypeOrFail($type): string
    {
        $type = is_string($type) ? strtoupper(trim($type)) : '';
        if (!in_array($type, [self::TXN_TYPE_IN, self::TXN_TYPE_OUT])) {
            throw new QiwiException('Invalid transaction type.');
        }

        return $type;
    }

    private function generateTxnId(): string
    {
        return number_format(floor(microtime(true) * 1000), 0, '', '');
    }

    /**
     * @return float
     * @throws QiwiException
     */
    public function getBalance(): float
    {
        $this->requireToken();

        $response = $this->requestGet('/funding-sources/v1/accounts/current');
        $this->requireSuccess($response);

        $accounts = getArrayFromArray($response->getData(), 'accounts');

        $wallet = null;
        foreach ($accounts as $account) {
            if (getBoolFromArray($account, 'hasBalance')) {
                $wallet = $account;
                break;
            }
        }
        if (!$wallet) {
            throw new QiwiException('Wallet not found.');
        }

        $amount = $wallet['balance']['amount'] ?? null;
        if (is_null($amount)) {
            throw new QiwiException('Balance not found.');
        }

        return strToFloat($amount);
    }

    /**
     * @return string
     * @throws QiwiException
     */
    public function getPhone(): string
    {
        $this->requireToken();

        $response = $this->requestGet('/person-profile/v1/profile/current');
        $this->requireSuccess($response);

        $contractInfo = getArrayFromArray($response->getData(), 'contractInfo');
        $contractId = getStrFromArray($contractInfo, 'contractId');
        if (!$contractId) {
            throw new QiwiException('Phone not found.');
        }

        return normalizePhone('+' . $contractId) ?: $contractId;
    }

    /**
     * @param string $receiver
     * @param float $amount
     * @param string $comment
     * @return QiwiTransaction
     * @throws QiwiException
     * @throws QiwiUncertaintyException
     */
    public function transfer(string $receiver, float $amount, string $comment = ''): QiwiTransaction
    {
        $this->requireToken();

        $receiver = $this->normalizePhoneOrFail($receiver);
        $amount = $this->normalizeAmountOrFail($amount);
        $comment = trim($comment);

        $fields = [
            'id' => $this->generateTxnId(),
            'sum' => [
                'amount' => $amount,
                'currency' => '643',
            ],
            'paymentMethod' => [
                'type' => 'Account',
                'accountId' => '643',
            ],
            'fields' => [
                'account' => $receiver,
            ],
            'comment' => $comment,
        ];
        $response = $this->requestPost('/sinap/api/v2/terms/99/payments', $fields);

        $data = $response->getData();
        $txn = getArrayFromArray($data, 'transaction');
        $txnId = getStrFromArray($txn, 'id');
        if (!$txnId) {
            $status = $response->getStatus();
            $error = $response->getError() ?: Phrases::ERROR_UNKNOWN;

            if (in_array($status, [400, 401])) {
                throw new QiwiException($error);
            }

            throw new QiwiUncertaintyException($error);
        }

        return (new QiwiTransaction())->setId($txnId);
    }

    /**
     * @param string $id
     * @param string $type
     * @return QiwiTransaction
     * @throws QiwiException
     */
    public function getTransaction(string $id, string $type): QiwiTransaction
    {
        $this->requireToken();

        $id = $this->normalizeTxnIdOrFail($id);
        $type = $this->normalizeTxnTypeOrFail($type);

        $response = $this->requestGet('/payment-history/v1/transactions/' . urlencode($id), ['type' => $type]);
        $this->requireSuccess($response);

        $txn = $response->getData();
        $txnId = getStrFromArray($txn, 'txnId');
        $commission = getArrayFromArray($txn, 'commission');
        $commissionAmount = getFloatFromArray($commission, 'amount');

        return (new QiwiTransaction())
            ->setId($txnId)
            ->setCommission($commissionAmount);
    }
}