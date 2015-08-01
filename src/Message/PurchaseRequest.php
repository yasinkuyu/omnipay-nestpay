<?php

namespace Omnipay\NestPay\Message;

use DOMDocument;
use Omnipay\Common\Message\AbstractRequest;

/**
 * NestPay Purchase Request
 * 
 * (c) Yasin Kuyu
 * 2015, insya.com
 * http://www.github.com/yasinkuyu/omnipay-nestpay
 */
class PurchaseRequest extends AbstractRequest {

    protected $endpoint = '';
    protected $endpoints = [
        'test'          => 'testsanalpos.est.com.tr',
        'isbank'        => 'spos.isbank.com.tr',
        'akbank'        => 'www.sanalakpos.com',
        'finansbank'    => 'www.fbwebpos.com',
        'halkbank'      => 'sanalpos.halkbank.com.tr',
        'anadolubank'   => 'anadolusanalpos.est.com.tr'
    ];
    
    protected $url = [
        "3dUrl"         => "/servlet/est3Dgate",
        "listUrl"       => "/servlet/listapproved",
        "detailUrl"     => "/servlet/cc5ApiServer",
        "cancelUrl"     => "/servlet/cc5ApiServer",
        "returnUrl"     => "/servlet/cc5ApiServer",
        "purchaseUrl"   => "/servlet/cc5ApiServer"
    ];
    
    public function getData() {
      
        $gateway = $this->getBank();
        $protocol = 'http://';

        if (!array_key_exists($gateway, $this->endpoints)) {
            throw new \Exception('Invalid Gateway');
        } else {
            $this->endpoint = $this->endpoints[$gateway];
        }
        $this->endpoint = $this->getTestMode() == TRUE ? $protocol . $this->endpoints["test"] . $this->url["purchaseUrl"] : $protocol . $this->endpoints[$gateway] . $this->url["purchaseUrl"];


        $this->validate('amount', 'card');
        $this->getCard()->validate();

        $data['Name'] = $this->getUserName();
        $data['ClientId'] = $this->getClientId();
        $data['Password'] = $this->getPassword();
        $data['Mode'] = 'P';
        $data['Email'] = $this->getCard()->getEmail();
        $data['OrderId'] = '';
        $data['GroupId'] = '';
        $data['TransId'] = '';
        $data['UserId'] = '';
        $data['Type'] = $this->getType();
        $data['Currency'] = 949; // TL
        $data['Taksit'] = $this->getInstallment();

        $data['Total'] = $this->getAmount(); 
        $data['Number'] = $this->getCard()->getNumber();
        $data['Expires'] = $this->getCard()->getExpiryDate('m') . $this->getCard()->getExpiryDate('y');
        $data["Cvv2Val"] = $this->getCard()->getCvv();
        $data["IPAddress"] = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;

        $data["BillTo"] =  '';
        $data["ShipTo"] =  '';
        $data["Extra"] =  '';
       
        return $data;
    }

    public function sendData($data) {
        $document = new DOMDocument('1.0', 'ISO-8859-9');

        $root = $document->createElement('CC5Request');

        // each array element 
        foreach ($data as $id => $value) {
            $root->appendChild($document->createElement($id, $value));
        }
        
        $document->appendChild($root);

        //post to NestPay
        $headers = array(
            'Content-Type'   => 'application/x-www-form-urlencoded'
        );
        
         // register the payment
        $this->httpClient->setConfig(array(
            'curl.options' => array(
                'CURLOPT_SSL_VERIFYHOST' => 2,
                'CURLOPT_SSLVERSION'     => 0,
                'CURLOPT_SSL_VERIFYPEER' => 0,
                'CURLOPT_RETURNTRANSFER' => 1,
                'CURLOPT_POST'           => 1
            )
        ));
        
        $httpResponse = $this->httpClient->post($this->endpoint, $headers, $document->saveXML())->send();

        return $this->response = new Response($this, $httpResponse->getBody());
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
}
