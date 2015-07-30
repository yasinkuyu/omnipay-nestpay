<?php

namespace Omnipay\NestPay\Message;

use DOMDocument;
use SimpleXMLElement;

/**
 * NestPay Purchase Request
 */
class RefundRequest extends PurchaseRequest
{
    public $transactionType = 'REFUND';

    public function getData()
    {
        $this->validate('transactionReference', 'amount', 'currency');

        $data = new SimpleXMLElement('<CC5Request/>');

        $data['Name'] = $this->getClientId();
        $data['ClientId'] = $this->getClientId();
        $data['Password'] = $this->getPassword();
        $data['Type'] =  'Credit';
        $data['Total'] = $this->getAmountInteger();
        $data['OrderId'] = $this->getTransactionId();
        $data['Currency'] = $this->getCurrencyNumeric();

        return $data;
    }
}
