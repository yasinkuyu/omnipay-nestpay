<?php namespace Omnipay\NestPay\Message;

use Omnipay\Tests\TestCase;

/**
 * NestPay Gateway ReferencedPurchaseRequestTest
 * 
 * (c) Yasin Kuyu
 * 2015, insya.com
 * http://www.github.com/yasinkuyu/omnipay-nestpay
 */
class ReferencedPurchaseRequestTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->request = new ReferencedPurchaseRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->request->initialize(
            array(
                'transactionReference'  => '0987654345678900987654',
                'amount'                => '10.00',
                'currency'              => 949,
                'testMode'              => true,
            )
        );
    }

    public function testGetData()
    {
        $data = $this->request->getData();

        /*
         * See https://bugs.php.net/bug.php?id=29500 for why this is cast to string
         */
        $this->assertSame('Auth', (string)$data['Type']);
        $this->assertSame('1200', (string)$data['Amount']);
        $this->assertSame('949', (string)$data['Currency']);
        $this->assertSame('0987654345678900987654', (string)$data['CrossReference']);
    }

}
