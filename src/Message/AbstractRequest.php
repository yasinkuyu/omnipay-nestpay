<?php namespace Omnipay\NestPay\Message;

/**
 * NestPay Purchase AbstractRequest
 * 
 * (c) Yasin Kuyu
 * 2015, insya.com
 * http://www.github.com/yasinkuyu/omnipay-nestpay
 */
abstract class AbstractRequest extends \Omnipay\Common\Message\AbstractRequest
{
     
    public function getUserName()
    {
        return $this->getParameter('name');
    }

    public function setUserName($value)
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

    public function getBank()
    {
        return $this->getParameter('bank');
    }
    
    public function setBank($value)
    {
        return $this->setParameter('bank', $value);
    }
}