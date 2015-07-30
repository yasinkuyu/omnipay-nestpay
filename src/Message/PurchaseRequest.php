<?php

namespace Omnipay\NestPay\Message;

use DOMDocument;
use SimpleXMLElement;
use Omnipay\Common\Message\AbstractRequest;

/**
 * NestPay Purchase Request
 */
class PurchaseRequest extends AbstractRequest
{
    protected $endpoint = 'https://testsanalpos.est.com.tr/servlet/cc5ApiServer'; //https://spos.isbank.com.tr/servlet/cc5ApiServer';

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

    public function getData()
    {
        $this->validate('amount', 'card');
        $this->getCard()->validate();

        $data = new SimpleXMLElement('<CC5Request/>');

        $data['Name'] = $this->getClientId();
        $data['ClientId'] = $this->getClientId();
        $data['Password'] = $this->getPassword();
        $data['Mode'] = 'P';
        $data['Taksit'] = 1;
        $data['Email'] = 1;
        $data['Total'] = $this->getAmountInteger();
        $data['Currency'] = $this->getCurrencyNumeric();
        $data['OrderId'] = $this->getTransactionId();
        $data['TransId'] = '';
        $data['Type'] = 'PostAuth';

        $data['Email']  = $this->getCard()->getEmail();
        $data['Number'] = $this->getCard()->getNumber();
        $data['Expires'] = $this->getCard()->getExpiryDate('m').$this->getCard()->getExpiryDate('y');
        $data["Cvv2Val"] = $this->getCard()->getCvv();
        $data["IPAddress"] =  '192.168.1.1'; // $_SERVER['REMOTE_ADDR']); $this->getClientIp();
        
        $data["BillTo"] =  '';  
        $data["ShipTo"] =  '';  

        return $data;
    }

    public function sendData($data)
    {
        // TODO: find PSR-0 SOAP library
        $document = new DOMDocument('1.0', 'ISO-8859-9');
        $envelope = $document->appendChild();
        
        $body = $envelope->appendChild();
        $body->appendChild($document->importNode(dom_import_simplexml($data), true));

        //post to NestPay
        $headers = array('Content-Type' => 'application/x-www-form-urlencoded');

        $httpResponse = $this->httpClient->post($this->endpoint, $headers, $document->saveXML())->send();

        return $this->response = new Response($this, $httpResponse->getBody());
    }
}
