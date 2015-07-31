<?php namespace Omnipay\NestPay\Message;

use DOMDocument;
use Omnipay\Common\Message\AbstractRequest;

/**
 * NestPay Purchase Request
 * 
 * (c) Yasin Kuyu
 * 2015, insya.com
 * http://www.github.com/yasinkuyu/omnipay-nestpay
 */
class PurchaseRequest extends AbstractRequest
{
    
    protected $endpoint = 'http://testsanalpos.est.com.tr/servlet/cc5ApiServer'; 

    protected $endpoints = [
        'test'           => 'testsanalpos.est.com.tr',
        'isbank'         => 'spos.isbank.com.tr',
        'akbank'         => 'www.sanalakpos.com',
        'finansbank'     => 'www.fbwebpos.com',
        'halkbank'       => 'sanalpos.halkbank.com.tr',
        'anadolubank'    => 'anadolusanalpos.est.com.tr'
    ];
 
    public function __construct()
    {
        $gateway = $this->getBank();
        
        if(!array_key_exists($gateway, $this->endpoints)){
            throw new \Exception('Invalid Gateway');
        }else{
            $this->endpoint = $this->endpoints[$gateway];
        }
        $this->endpoint = $this->mode == 'TEST' ? 'http://'. $this->endpoints["test"] .'/servlet/cc5ApiServer' : 'http://'.$this->endpoints[$gateway].'/servlet/cc5ApiServer';
    }
    
    public function getData()
    {
        $this->validate('amount', 'card');
        $this->getCard()->validate();

        $data['Name'] = $this->getUserName();
        $data['ClientId'] = $this->getClientId();
        $data['Password'] = $this->getPassword();
        $data['Mode'] = 'P';
        $data['Email'] = "yasinkuyu@gmail.com";
        $data['OrderId'] = '';
        $data['GroupId'] = '';
        $data['TransId'] = '';
        $data['UserId'] = '';
        $data['Type'] = 'Auth'; //PreAuth
        $data['Currency'] = 949; //$this->getCurrencyNumeric();
        $data['Taksit'] = 0;

        $data['Total'] = $this->getAmountInteger();
        $data['Email']  = $this->getCard()->getEmail();
        $data['Number'] = $this->getCard()->getNumber();
        $data['Expires'] = $this->getCard()->getExpiryDate('m').$this->getCard()->getExpiryDate('y');
        $data["Cvv2Val"] = $this->getCard()->getCvv();
        $data["IPAddress"] = "127.0.0.1";//$this->getClientIp();
        
        /*
        $data["BillTo"] =  '';  
        $data["ShipTo"] =  '';  
        $data["Extra"] =  '';  
        */
        
        return $data;
  
    }

    public function sendData($data)
    {
        $document = new DOMDocument('1.0', 'ISO-8859-9');
            
        $root = $document->createElement('CC5Request');
    
        foreach($data as $id => $value)
        {
            $root->appendChild($document->createElement($id, $value));
        }
        $document->appendChild($root);

        //post to NestPay
        $headers = array('Content-Type' => 'application/x-www-form-urlencoded');

        $httpResponse = $this->httpClient->post($this->endpoint, $headers, $document->saveXML())->send();

        return $this->response = new Response($this, $httpResponse->getBody());
    }
}
