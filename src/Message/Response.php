<?php

namespace Omnipay\NestPay\Message;

use DOMDocument;
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

        $responseDom = new DOMDocument;
        $responseDom->loadXML($data);
        $this->data = simplexml_import_dom($responseDom->CC5Response->ProcReturnCode);

        $resultElement = $this->getResultElement();
        if (!isset($resultElement->CC5Response->ErrMsg)) {
            throw new InvalidResponseException;
        }
    }

    public function getResultElement()
    {
        $resultElement = preg_replace('/Response$/', 'Result', $this->data->getName());

        return $this->data->$resultElement;
    }

    public function isSuccessful()
    {
        return 0 === (int) $this->getResultElement()->CC5Response->ProcReturnCode;
    }

    public function isRedirect()
    {
        return 3 === (int) $this->getResultElement()->CC5Response->ErrMsg;
    }

    public function getTransactionReference()
    {
        return (string) $this->data->CC5Response->ProcReturnCode;
    }

    public function getMessage()
    {
        return (string) $this->getResultElement()->response;
    }


    public function getRedirectMethod()
    {
        return 'POST';
    }

   
}
