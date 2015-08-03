<?php namespace Omnipay\NestPay;

use Omnipay\Common\CreditCard;
use Omnipay\Tests\GatewayTestCase;

/**
 * NestPay Gateway Test
 * 
 * (c) Yasin Kuyu
 * 2015, insya.com
 * http://www.github.com/yasinkuyu/omnipay-nestpay
 */
class GatewayTest extends GatewayTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->gateway = new Gateway($this->getHttpClient(), $this->getHttpRequest());

        $this->options = array(
            'bank' => 'denizbank',
            'name' => 'DENIZTEST',
            'clientId' => '800100000',
            'password' => 'DENIZTEST123',
            'amount' => 10.00,
            'currency' => 'TRY',
            'returnUrl' => 'http://sanalmagaza.org/return',
            'card' => new CreditCard(array(
                'number'        => '5406675406675403',
                'expiryMonth'   => '12',
                'expiryYear'    => '2015',
                'cvv'           => '000',
                'email'         => 'yasinkuyu@gmail.com',
                'firstname'     => 'Yasin',
                'lastname'      => 'Kuyu'
            )),
        );
    }

    public function testPurchase()
    {
        $this->setMockHttpResponse('PurchaseSuccess.txt');

        $response = $this->gateway->purchase($this->options)->send();

        $this->assertInstanceOf('\Omnipay\NestPay\Message\Response', $response);
        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('130215141054377801316798', $response->getTransactionReference());
    }

    public function testPurchaseError()
    {
        $this->setMockHttpResponse('PurchaseFailure.txt');

        $response = $this->gateway->purchase($this->options)->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertSame('Input variable errors', $response->getMessage());
    }
}
