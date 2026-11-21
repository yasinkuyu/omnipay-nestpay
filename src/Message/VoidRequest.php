<?php

namespace Omnipay\NestPay\Message;

/** İptal (Void): yalnız aynı gün, gün sonundan önce; tutarın tamamı, karta yansımaz. transactionId = sipariş no (oid). */
class VoidRequest extends AbstractXmlRequest
{
    protected function body(): array
    {
        $this->validate('transactionId');

        return ['Type' => 'Void', 'OrderId' => (string) $this->getTransactionId()];
    }
}
