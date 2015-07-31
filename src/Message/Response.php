<?php namespace Omnipay\NestPay\Message;

use SimpleXMLElement;
use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;
use Omnipay\Common\Message\RequestInterface;

/**
 * NestPay Response
 * 
 * (c) Yasin Kuyu
 * 2015, insya.com
 * http://www.github.com/yasinkuyu/omnipay-nestpay
 */
class Response extends AbstractResponse implements RedirectResponseInterface
{
    public function __construct(RequestInterface $request, $data)
    {
        $this->request = $request;
        $this->data = SimpleXMLElement($data);
    }

    public function isSuccessful()
    {
        return $this->xml->CC5Response->ProcReturnCode === '00';
    }

    public function isRedirect()
    {
        return 3 === (int) $this->data->error_msg;
    }

    public function getTransactionId()
    {
        return $this->data->transid;
    }

    public function getMessage()
    {
        return $this->data->response + " / "  + $this->data->host_msg;
    }

    public function getError()
    {
        return $this->data->return_code;
    }

    public function getErrorMsg()
    {
        return $this->data->error_msg;
    }

    public function getRequest()
    {
        return $this->request;
    }
    
    public function getRedirectMethod()
    {
        return 'POST';
    }
   
}