<?php

namespace Omnipay\NestPay\Message;

/**
 * NestPay Purchase Request
 */
class ReferencedPurchaseRequest extends RefundRequest
{
    public $transactionType = 'Auth';
}
