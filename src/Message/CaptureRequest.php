<?php

namespace Omnipay\NestPay\Message;

/** Ön provizyonu kapatır (PostAuth). amount verilmezse provizyonun tamamı. */
class CaptureRequest extends AbstractXmlRequest
{
    protected function body(): array
    {
        $this->validate('transactionId');
        $data = ['Type' => 'PostAuth', 'OrderId' => (string) $this->getTransactionId()];
        if ($this->getParameter('amount') !== null) {
            $data['Total'] = $this->getAmount();
            $data['Currency'] = $this->getCurrencyNumeric();
        }

        return $data;
    }
}
