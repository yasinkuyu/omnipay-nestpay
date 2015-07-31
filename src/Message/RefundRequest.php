<?php namespace Omnipay\NestPay\Message;

use DOMDocument;
use SimpleXMLElement;

/**
 * NestPay Purchase Request
 * 
 * (c) Yasin Kuyu
 * 2015, insya.com
 * http://www.github.com/yasinkuyu/omnipay-nestpay
 */
class RefundRequest extends PurchaseRequest
{
    public $transactionType = 'REFUND';

    public function getData()
    {
        $this->validate('transactionReference', 'amount', 'currency');

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
