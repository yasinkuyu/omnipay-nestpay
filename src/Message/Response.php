<?php

namespace Omnipay\NestPay\Message;

use DOMDocument;
use SimpleXMLElement;
use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;
use Omnipay\Common\Message\RequestInterface;

/**
 * NestPay Response
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
        return (string) $this->xml->CC5Response->ProcReturnCode === '00';
    }

    public function isRedirect()
    {
        return false;//3 === (int) $this->data->error_msg;
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
