<?php

namespace Omnipay\NestPay\Message;

/**
 * 3D ödeme formu. Ağ isteği yapmaz: imzalı alanları döndürür, tarayıcı bunları bankaya POST eder.
 *
 * Parametreler: amount, currency, transactionId (oid, sipariş no), returnUrl (okUrl), cancelUrl (failUrl, yoksa returnUrl),
 * notifyUrl (callbackUrl, isteğe bağlı), installment, card (yalnız e-posta / ad / telefon için; kart numarası gönderilmez
 * — 3d_pay ve 3d modellerinde kart alanları tarayıcıdaki formda müşteri tarafından doldurulur).
 */
class PurchaseRequest extends AbstractRequest
{
    protected string $tranType = 'Auth';

    public function getData(): array
    {
        $this->validate('clientId', 'storeKey', 'amount', 'transactionId', 'returnUrl');

        $installment = (int) $this->getInstallment();
        $data = [
            'clientid' => (string) $this->getClientId(),
            'storetype' => (string) ($this->getStoreType() ?: '3d_pay_hosting'),
            'hashAlgorithm' => 'ver3',
            'TranType' => $this->tranType,
            'amount' => $this->getAmount(),
            'currency' => $this->getCurrencyNumeric(),
            'oid' => (string) $this->getTransactionId(),
            'okUrl' => (string) $this->getReturnUrl(),
            'failUrl' => (string) ($this->getCancelUrl() ?: $this->getReturnUrl()),
            'Instalment' => $installment > 1 ? (string) $installment : '',
            'lang' => (string) ($this->getLang() ?: 'tr'),
            'rnd' => bin2hex(random_bytes(10)),
        ];
        if ($this->getRefreshTime() !== null && $this->getRefreshTime() !== '') {
            $data['refreshtime'] = (string) (int) $this->getRefreshTime();
        }
        if ($this->getNotifyUrl()) {
            $data['callbackUrl'] = (string) $this->getNotifyUrl();
        }
        if ($card = $this->getCard()) {
            $data += array_filter([
                'email' => mb_substr((string) $card->getEmail(), 0, 64),
                'tel' => mb_substr((string) $card->getBillingPhone(), 0, 32),
                'BillToName' => mb_substr(trim((string) $card->getBillingName()), 0, 64),
            ], fn ($v) => $v !== '');
        }
        if ($this->getDescription()) {
            $data['description'] = mb_substr((string) $this->getDescription(), 0, 255);
        }

        $data['hash'] = self::sign($data, (string) $this->getStoreKey());
        $data['encoding'] = 'utf-8';

        return $data;
    }

    public function sendData($data): PurchaseResponse
    {
        return $this->response = new PurchaseResponse($this, $data, $this->getGatewayUrl());
    }
}
