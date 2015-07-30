<?php

namespace Omnipay\NestPay\Message;

use Omnipay\Tests\TestCase;

class RefundRequestTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->request = new RefundRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->request->initialize(
            array(
                'amount' => '12.00',
                'transactionReference' => '0987654345678900987654',
                'currency' => 'GBP',
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
        $this->assertSame('REFUND', (string)$data['Type']);
        $this->assertSame('1200', (string)$data['Amount']);
        $this->assertSame('826', (string)$data['Currency']);
        $this->assertSame('0987654345678900987654', (string)$data['CrossReference']);
    }

}
