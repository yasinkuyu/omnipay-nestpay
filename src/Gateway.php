<?php

namespace Omnipay\NestPay;

use Omnipay\Common\AbstractGateway;

/**
 * NestPay Gateway
 * 
 * (c) Yasin Kuyu
 * 2015, insya.com
 * http://www.github.com/yasinkuyu/omnipay-nestpay
 */
class Gateway extends AbstractGateway {

    public function getName() {
        return 'NestPay';
    }

    public function getDefaultParameters() {
        return array(
            'bank' => '',
            'username' => '',
            'clientId' => '',
            'password' => '',
            'installment' => '',
            'type' => 'Auth',
            'currency' => 'TRY',
            'testMode' => false
        );
    }

    public function authorize(array $parameters = array()) {
        return $this->createRequest('\Omnipay\NestPay\Message\AuthorizeRequest', $parameters);
    }

    public function capture(array $parameters = array()) {
        return $this->createRequest('\Omnipay\NestPay\Message\CaptureRequest', $parameters);
    }

    public function purchase(array $parameters = array()) {
        return $this->createRequest('\Omnipay\NestPay\Message\PurchaseRequest', $parameters);
    }

    public function refund(array $parameters = array()) {
        return $this->createRequest('\Omnipay\NestPay\Message\RefundRequest', $parameters);
    }

    public function void(array $parameters = array()) {
        return $this->createRequest('\Omnipay\NestPay\Message\VoidRequest', $parameters);
    }

    public function credit(array $parameters = array()) {
        return $this->createRequest('\Omnipay\NestPay\Message\CreditRequest', $parameters);
    }

    public function settle(array $parameters = array()) {
        return $this->createRequest('\Omnipay\NestPay\Message\SettleRequest', $parameters);
    }

    public function money(array $parameters = array()) {
        return $this->createRequest('\Omnipay\NestPay\Message\MoneyPointsRequest', $parameters);
    }

    public function getBank() {
        return $this->getParameter('bank');
    }

    public function setBank($value) {
        return $this->setParameter('bank', $value);
    }

    public function getUserName() {
        return $this->getParameter('username');
    }

    public function setUserName($value) {
        return $this->setParameter('username', $value);
    }

    public function getClientId() {
        return $this->getParameter('clientId');
    }

    public function setClientId($value) {
        return $this->setParameter('clientId', $value);
    }

    public function getPassword() {
        return $this->getParameter('password');
    }

    public function setPassword($value) {
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

    public function getOrderId() {
        return $this->getParameter('orderid');
    }

    public function setOrderId($value) {
        return $this->setParameter('orderid', $value);
    }

    public function getMoneyPoints() {
        return $this->getParameter('moneypoints');
    }

    public function setMoneyPoints($value) {
        return $this->setParameter('moneypoints', $value);
    }

    public function getSettlement() {
        return $this->getParameter('settlement');
    }

    public function setSettlement($value) {
        return $this->setParameter('settlement', $value);
    }

}
