<?php namespace Omnipay\NestPay;

use Omnipay\NestPay\Message\CompletePurchaseRequest;
use Omnipay\NestPay\Message\PurchaseRequest;
use Omnipay\Common\AbstractGateway;

/**
 * NestPay Gateway
 * 
 * (c) Yasin Kuyu
 * 2015, insya.com
 * http://www.github.com/yasinkuyu/omnipay-nestpay
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
            
            'bank'          => '',
            'username'      => '',
            'clientId'      => '',
            'password'      => '',
            'installment'   => '0',
            'type'          => 'PreAuth'
            
        );
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
    
    public function getBank()
    {
        return $this->getParameter('bank');
    }
    
    public function setBank($value)
    {
        return $this->setParameter('bank', $value);
    }
    
    public function getUserName()
    {
        return $this->getParameter('username');
    }

    public function setUserName($value)
    {
        return $this->setParameter('username', $value);
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
    
    public function getInstallment() {
        return $this->getParameter('installment');
    }

    public function setInstallment($value) {
        return $this->setParameter('installment', $value);
    }
       
    public function getType() {
        return $this->getParameter('type');
    }

    public function setType($value) {
        return $this->setParameter('type', $value);
    }
}
