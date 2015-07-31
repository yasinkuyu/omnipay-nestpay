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
            'name' => 'ISBANKAPI',
            'clientId' => '700100000',
            'password' => 'ISBANK07',
            'amount' => '10.00',
            'currency' => 'TL',
            'returnUrl' => 'http://sanalmagaza.org/return',
            'card' => new CreditCard(array(
                'firstName' => 'Yasin',
                'lastName' => 'Kuyu',
                'number' => '4508034508034509',
                'expiryMonth' => '12',
                'expiryYear' => '16',
                'cvv' => '000'
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
