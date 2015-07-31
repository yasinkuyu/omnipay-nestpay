<?php namespace Omnipay\NestPay;

use Omnipay\NestPay\Message\CompletePurchaseRequest;
use Omnipay\NestPay\Message\PurchaseRequest;
use Omnipay\Common\AbstractGateway;

/**
 * NestPay Gateway
 *
 * @link http://www.github.com/yasinkuyu/omnipay-nestpay
 */
class Gateway extends AbstractGateway
{
    public function getName()
    {
        return 'NestPay';
    }

    public function getDefaultParameters()
    {
        return array(
            'name' => '',
            'clientId' => '',
            'password' => '',
        );
    }

    public function getVendorName()
    {
        return $this->getParameter('name');
    }

    public function setVendorName($value)
    {
        return $this->setParameter('name', $value);
    }

    public function getClientId()
    {
        return $this->getParameter('clientId');
    }

    public function setClientId($value)
    {
        return $this->setParameter('clientId', $value);
    }

    public function getPassword()
    {
        return $this->getParameter('password');
    }

    public function setPassword($value)
    {
        return $this->setParameter('password', $value);
    }

    public function purchase(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\NestPay\Message\PurchaseRequest', $parameters);
    }

    public function referencedPurchase(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\NestPay\Message\ReferencedPurchaseRequest', $parameters);
    }

    public function completePurchase(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\NestPay\Message\CompletePurchaseRequest', $parameters);
    }

    public function refund(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\NestPay\Message\RefundRequest', $parameters);
    }
}
