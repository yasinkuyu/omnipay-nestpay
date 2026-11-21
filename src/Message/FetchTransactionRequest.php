<?php

namespace Omnipay\NestPay\Message;

/** Sipariş durumu sorgusu (ORDERSTATUS). Sonuç alanları XmlResponse::getExtra() içinde (TRANS_STAT, CAPTURE_AMT …). */
class FetchTransactionRequest extends AbstractXmlRequest
{
    protected function body(): array
    {
        $this->validate('transactionId');

        return ['OrderId' => (string) $this->getTransactionId(), 'Extra' => ['ORDERSTATUS' => 'QUERY']];
    }
}
