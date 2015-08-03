<?php namespace Omnipay\NestPay\Message;

use Mockery as m;
use Omnipay\Tests\TestCase;

/**
 * NestPay Gateway ResponseTest
 * 
 * (c) Yasin Kuyu
 * 2015, insya.com
 * http://www.github.com/yasinkuyu/omnipay-nestpay
 */
class ResponseTest extends TestCase
{
    /**
     * @expectedException \Omnipay\Common\Exception\InvalidResponseException
     */
    public function testPurchaseWithoutStatusCode()
    {
        $httpResponse = $this->getMockHttpResponse('PurchaseFailureWithoutStatusCode.txt');
        new Response($this->getMockRequest(), $httpResponse->getBody());
    }

    public function testPurchaseSuccess()
    {
        $httpResponse = $this->getMockHttpResponse('PurchaseSuccess.txt');
        $response = new Response($this->getMockRequest(), $httpResponse->getBody());

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('130215141054377801316798', $response->getTransactionReference());
        $this->assertSame('AuthCode: 672167', $response->getMessage());
        $this->assertEmpty($response->getRedirectUrl());
    }

    public function testPurchaseFailure()
    {
        $httpResponse = $this->getMockHttpResponse('PurchaseFailure.txt');
        $response = new Response($this->getMockRequest(), $httpResponse->getBody());

        $this->assertFalse($response->isSuccessful());
        $this->assertSame('', $response->getTransactionReference());
        $this->assertSame('Input variable errors', $response->getMessage());
    }

    public function testRedirect()
    {
        $httpResponse = $this->getMockHttpResponse('PurchaseRedirect.txt');

        $request = m::mock('\Omnipay\Common\Message\AbstractRequest');
        $request->shouldReceive('getReturnUrl')->once()->andReturn('http://sanalmagaza.org/');

        $response = new Response($request, $httpResponse->getBody());

        $this->assertTrue($response->isRedirect());
        $this->assertSame('POST', $response->getRedirectMethod());
        $this->assertSame('http://sanalmagaza.org/', $response->getRedirectUrl());

        $expectedData = array(
            'ReturnUrl' => 'http://sanalmagaza.org/',
            'ReferanceId' => '130215141054377801316798'
        );
        $this->assertEquals($expectedData, $response->getRedirectData());
    }
}
