<?php

namespace Omnipay\NestPay\Message;

/** Ön provizyon (PreAuth); tutar capture() ile çekilir */
class AuthorizeRequest extends PurchaseRequest
{
    protected string $tranType = 'PreAuth';
}
