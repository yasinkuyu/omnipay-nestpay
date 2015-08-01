<?php

namespace Omnipay\NestPay\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RequestInterface;
use Omnipay\Common\Message\RedirectResponseInterface;
use Omnipay\Common\Exception\InvalidResponseException;
use SimpleXMLElement;

/**
 * NestPay Response
 *
 * (c) Yasin Kuyu
 * 2015, insya.com
 * http://www.github.com/yasinkuyu/omnipay-nestpay
 */
class Response extends AbstractResponse implements RedirectResponseInterface {

    /**
     * Constructor
     *
     * @param  RequestInterface         $request
     * @param  string                   $data
     * @throws InvalidResponseException
     */
    public function __construct(RequestInterface $request, $data) {
        $this->request = $request;
        try {
            $this->data = new SimpleXMLElement($data);
        } catch (\Exception $ex) {
            throw new InvalidResponseException();
        }
    }

    /**
     * Whether or not response is successful
     *
     * @return bool
     */
    public function isSuccessful() {
        return (string) $this->data->CC5Response->ProcReturnCode === '00' || $this->data->CC5Response->Response === 'Approved';
    }

    /**
     * Get is redirect
     *
     * @return bool
     */
    public function isRedirect() {
        return false; //todo
    }

    /**
     * Get transaction reference
     *
     * @return string
     */
    public function getTransactionReference() {
        return (string) $this->data->transid;
    }

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage() {
        if ($this->isSuccessful()) {
            return $this->data->host_msg;
        }
        return $this->data->CC5Response->error_msg;
    }

    /**
     * Get Redirect url
     *
     * @return string
     */
    public function getRedirectUrl() {
        if ($this->isRedirect()) {
            $data = array(
                'TransId' => $this->data->TransId
            );
            return $this->getRequest()->getEndpoint() . '/test/index?' . http_build_query($data);
        }
    }

    public function getError() {
        if ($this->isSuccessful()) {
            return [];
        }
        return $this->data->CC5Response->error_msg;
    }

    public function getRedirectMethod() {
        return 'POST';
    }

    public function getRedirectData() {
        return null;
    }

}
