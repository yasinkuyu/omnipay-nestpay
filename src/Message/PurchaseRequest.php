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
        'test' => 'https://testvpos.asseco-see.com.tr/fim/api',
        'asseco' => 'https://entegrasyon.asseco-see.com.tr/fim/api',
        'isbank' => 'spos.isbank.com.tr',
        'akbank' => 'www.sanalakpos.com',
        'finansbank' => 'www.fbwebpos.com',
        'denizbank' => 'denizbank.est.com.tr',
        'kuveytturk' => 'kuveytturk.est.com.tr',
        'halkbank' => 'sanalpos.halkbank.com.tr',
        'anadolubank' => 'anadolusanalpos.est.com.tr',
        'hsbc' => 'vpos.advantage.com.tr',
        'ziraatbank' => 'sanalpos2.ziraatbank.com.tr',
        
        // Todo
        'ingbank' => 'ingbank.est.com.tr',
        'citibank' => 'citibank.est.com.tr',
        'cardplus' => 'cardplus.est.com.tr'
    ];
    protected $url = [
        "3d" => "/servlet/est3Dgate",
        "3dhsbc" => "/servlet/hsbc3Dgate",
        "list" => "/servlet/listapproved",
        "detail" => "/servlet/cc5ApiServer",
        "cancel" => "/servlet/cc5ApiServer",
        "return" => "/servlet/cc5ApiServer",
        "purchase" => "/servlet/cc5ApiServer"
    ];
    protected $currencies = [
        'TRY' => 949,
        'YTL' => 949,
        'TRL' => 949,
        'TL' => 949,
        'USD' => 840,
        'EUR' => 978,
        'GBP' => 826,
        'JPY' => 392
    ];

    public function getData() {

        $this->validate('amount', 'card');
        $this->getCard()->validate();
        $currency = $this->getCurrency();

        $data['Email'] = $this->getCard()->getEmail();
        $data['OrderId'] = $this->getOrderId();
        $data['GroupId'] = '';
        $data['TransId'] = '';
        $data['UserId'] = '';
        $data['Type'] = $this->getType();
        $data['Currency'] = $this->currencies[$currency];
        $data['Installment'] = $this->getInstallment();

        $data['Total'] = $this->getAmount();
        $data['Number'] = $this->getCard()->getNumber();
        $data['Expires'] = $this->getCard()->getExpiryDate('my');
        $data["Cvv2Val"] = $this->getCard()->getCvv();
        $data["IPAddress"] = $this->getClientIp();

        return $data;
    }

    public function sendData($data) {

        // API info
        $data['Name'] = $this->getUserName();
        $data['ClientId'] = $this->getClientId();
        $data['Password'] = $this->getPassword();
        $data['Mode'] = $this->getTestMode() ? 'T' : 'P';

        // Get geteway
        // ex: isbank
        $gateway = $this->getBank();

        // Todo: http protocol
        $protocol = 'https://';

        if (!array_key_exists($gateway, $this->endpoints)) {
            throw new \Exception('Invalid Gateway');
        } else {
            $this->endpoint = $this->endpoints[$gateway];
        }

        // Build api post url
        $this->endpoint = $this->getTestMode() == TRUE ? $this->endpoints["test"] : $protocol . $this->endpoints[$gateway] . $this->url["purchase"];

        $document = new DOMDocument('1.0', 'UTF-8');
        $root = $document->createElement('CC5Request');

        // Each array element 
        foreach ($data as $id => $value) {
            $root->appendChild($document->createElement($id, $value));
        }

        $document->appendChild($root);

        if (!empty($this->getCard()->getFirstName())) {
            $dataShip = [
                "Name" => $this->getCard()->getFirstName() . " " . $this->getCard()->getLastName(),
                "Street1" => $this->getCard()->getShippingAddress1(),
                "Street2" => $this->getCard()->getShippingAddress2(),
                "Street3" => "",
                "City" => $this->getCard()->getShippingCity(),
                "StateProv" => $this->getCard()->getShippingState(),
                "PostalCode" => $this->getCard()->getShippingPostcode(),
                "Country" => $this->getCard()->getShippingCountry(),
                "Company" => $this->getCard()->getCompany(),
                "TelVoice" => $this->getCard()->getShippingPhone()
            ];

            $shipTo = $document->createElement('ShipTo');
            foreach ($dataShip as $id => $value) {
                $shipTo->appendChild($document->createElement($id, $value));
            }
            $root->appendChild($shipTo);

            $dataBill = [
                "Name" => $this->getCard()->getFirstName() . " " . $this->getCard()->getLastName(),
                "Street1" => $this->getCard()->getBillingAddress1(),
                "Street2" => $this->getCard()->getBillingAddress2(),
                "Street3" => "",
                "City" => $this->getCard()->getBillingCity(),
                "StateProv" => $this->getCard()->getBillingState(),
                "PostalCode" => $this->getCard()->getBillingPostcode(),
                "Country" => $this->getCard()->getBillingCountry(),
                "Company" => $this->getCard()->getCompany(),
                "TelVoice" => $this->getCard()->getBillingPhone()
            ];
        }

        // Set money points (maxi puan)
        $extra = $document->createElement('Extra');
        if (!empty($this->getMoneyPoints())) {
            $extra->appendChild($document->createElement('MAXIPUAN', $this->getMoneyPoints()));
            $root->appendChild($extra);
        }

        // Get money points (maxi puan)
        if (!empty($this->getMoneyPoints())) {
            $extra->appendChild($document->createElement('MAXIPUANSORGU', 'MAXIPUANSORGU'));
            $root->appendChild($extra);
        }

        // Settlement
        if (!empty($this->getSettlement())) {
            $extra->appendChild($document->createElement('SETTLE', 'SETTLE'));
            $root->appendChild($extra);
        }

        $billTo = $document->createElement('BillTo');
        foreach ($dataBill as $id => $value) {
            $billTo->appendChild($document->createElement($id, $value));
        }
        $root->appendChild($billTo);

        // Post to NestPay
        $headers = array(
            'Content-Type' => 'application/x-www-form-urlencoded'
        );

        // Register the payment
        $this->httpClient->setConfig(array(
            'curl.options' => array(
                'CURLOPT_SSL_VERIFYHOST' => 2,
                'CURLOPT_SSLVERSION' => 0,
                'CURLOPT_SSL_VERIFYPEER' => 0,
                'CURLOPT_RETURNTRANSFER' => 1,
                'CURLOPT_POST' => 1
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
