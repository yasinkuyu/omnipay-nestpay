<?php namespace Omnipay\NestPay\Message;

use Omnipay\Tests\TestCase;

/**
 * NestPay Gateway Refund RequestTest
 * 
 * (c) Yasin Kuyu
 * 2015, insya.com
 * http://www.github.com/yasinkuyu/omnipay-nestpay
 */
class RefundRequestTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->request = new RefundRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->request->initialize(
            array(
                'amount' => '11.00',
                'orderId' => 'ORDER-365123',
                'currency' => 'TRY',
                'testMode' => true,
            )
        );
    }

    public function testGetData()
    {
        $data = $this->request->getData();

        /*
         * See https://bugs.php.net/bug.php?id=29500 for why this is cast to string
         */
        $this->assertSame('Credit', (string)$data['Type']);
        $this->assertSame('1200', (string)$data['Amount']);
        $this->assertSame('TRY', (string)$data['Currency']);
        $this->assertSame('ORDER-365123', (string)$data['orderId']);
    }

}
