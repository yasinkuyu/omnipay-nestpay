<?php

namespace Omnipay\NestPay\Message;

/**
 * NestPay Complete Capture Request
 * 
 * (c) Yasin Kuyu
 * 2015, insya.com
 * http://www.github.com/yasinkuyu/omnipay-nestpay
 */
class CaptureRequest extends PurchaseRequest {

    public function getData() {

        $this->validate('orderid', 'amount');
        $currency = $this->getCurrency();

        $data['Type'] = 'PostAuth';
        $data['OrderId'] = $this->getOrderId();
        $data['Currency'] = $this->currencies[$currency];
        $data['Total'] = $this->getAmount();

        return $data;
    }

}
