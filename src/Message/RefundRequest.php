<?php

namespace Omnipay\NestPay\Message;

/** İade (Credit): transactionId = sipariş no (oid), amount = iade tutarı. Gün sonundan sonra / kısmi tutarlar için. */
class RefundRequest extends AbstractXmlRequest
{
    protected function body(): array
    {
        $this->validate('transactionId', 'amount');

        return ['Type' => 'Credit', 'OrderId' => (string) $this->getTransactionId(), 'Total' => $this->getAmount(), 'Currency' => $this->getCurrencyNumeric()];
    }
}
