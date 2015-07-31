<?php namespace Omnipay\NestPay\Message;

/**
 * NestPay Purchase Request
 * 
 * (c) Yasin Kuyu
 * 2015, insya.com
 * http://www.github.com/yasinkuyu/omnipay-nestpay
 */
class ReferencedPurchaseRequest extends RefundRequest
{
    public $transactionType = 'Auth';
}
