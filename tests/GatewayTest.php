<?php

namespace Omnipay\NestPay\Tests;

use Omnipay\NestPay\Gateway;
use Omnipay\NestPay\Message\AbstractRequest;
use Omnipay\Tests\GatewayTestCase;

class GatewayTest extends GatewayTestCase
{
    /** @var Gateway */
    protected $gateway;

    public function setUp(): void
    {
        parent::setUp();
        $this->gateway = new Gateway($this->getHttpClient(), $this->getHttpRequest());
        $this->gateway->initialize([
            'clientId' => '700655000200',
            'storeKey' => 'TRPS0200',
            'username' => 'ISBANKAPI',
            'password' => 'ISBANK07',
            'testMode' => true,
        ]);
    }

    public function testSignatureMatchesVer3Rules(): void
    {
        // ada göre doğal sıra (büyük/küçük harf duyarsız), \ ve | kaçışı, hash / encoding / nationalidno hariç, sonda anahtar
        $fields = ['b' => 'x|y', 'A' => 'a\\b', 'hash' => 'zzz', 'encoding' => 'utf-8', 'c10' => '2', 'c2' => '1', 'NationalIdNo' => '1'];
        $expected = base64_encode(hash('sha512', 'a\\\\b|x\\|y|1|2|KEY\\|1', true));
        $this->assertSame($expected, AbstractRequest::sign($fields, 'KEY|1'));
    }

    public function testPurchaseBuildsSignedHostingForm(): void
    {
        $response = $this->gateway->purchase([
            'amount' => '10.50',
            'currency' => 'TRY',
            'transactionId' => 'ORD-1',
            'returnUrl' => 'https://shop.test/return',
            'notifyUrl' => 'https://shop.test/notify',
            'installment' => 3,
            'card' => ['email' => 'a@b.test', 'billingFirstName' => 'Ada', 'billingLastName' => 'Kaya'],
        ])->send();

        $this->assertTrue($response->isRedirect());
        $this->assertFalse($response->isSuccessful());
        $this->assertSame('POST', $response->getRedirectMethod());
        $this->assertSame(AbstractRequest::TEST_GATE, $response->getRedirectUrl());

        $d = $response->getRedirectData();
        $this->assertSame('3d_pay_hosting', $d['storetype']);
        $this->assertSame('10.50', $d['amount']);
        $this->assertSame('949', $d['currency']);
        $this->assertSame('3', $d['Instalment']);
        $this->assertSame('https://shop.test/return', $d['failUrl']);
        $this->assertSame('https://shop.test/notify', $d['callbackUrl']);
        $this->assertSame('Ada Kaya', $d['BillToName']);
        $this->assertArrayNotHasKey('pan', $d);
        $this->assertSame(AbstractRequest::sign($d, 'TRPS0200'), $d['hash']);
    }

    public function testLiveBankEndpoints(): void
    {
        $this->gateway->setTestMode(false);
        $this->gateway->setBank('akbank');
        $r = $this->gateway->purchase(['amount' => '1.00', 'transactionId' => 'X', 'returnUrl' => 'https://shop.test/r'])->send();
        $this->assertSame('https://www.sanalakpos.com/fim/est3Dgate', $r->getRedirectUrl());
    }

    private function bankReturn(array $override = []): array
    {
        $p = array_merge([
            'clientid' => '700655000200', 'oid' => 'ORD-1', 'amount' => '10.50', 'Response' => 'Approved',
            'ProcReturnCode' => '00', 'mdStatus' => '1', 'TransId' => '24321ABC', 'AuthCode' => 'P12345',
            'MaskedPan' => '454671***7894', 'Instalment' => '3', 'TDS2.authTimestamp' => '1732190000', 'rnd' => 'abc',
        ], $override);
        $p['HASH'] = AbstractRequest::sign($p, 'TRPS0200');

        return $p;
    }

    private function postBody(array $fields): void
    {
        // nokta içeren alan adlarının bozulmadan okunduğunu sınamak için ham gövde
        $req = \Symfony\Component\HttpFoundation\Request::create('/return', 'POST', [], [], [], [], http_build_query($fields));
        $this->gateway = new Gateway($this->getHttpClient(), $req);
        $this->gateway->initialize(['clientId' => '700655000200', 'storeKey' => 'TRPS0200', 'testMode' => true]);
    }

    public function testCompletePurchaseApproved(): void
    {
        $this->postBody($this->bankReturn());
        $r = $this->gateway->completePurchase(['transactionId' => 'ORD-1', 'amount' => '10.50'])->send();

        $this->assertTrue($r->isVerified());
        $this->assertTrue($r->isSuccessful());
        $this->assertSame('ORD-1', $r->getTransactionId());
        $this->assertSame('24321ABC', $r->getTransactionReference());
        $this->assertSame(3, $r->getInstallment());
        $this->assertSame('454671***7894', $r->getMaskedCard());
        $this->assertSame('completed', $r->getTransactionStatus());
    }

    public function testCompletePurchaseRejectsTamperedFields(): void
    {
        $p = $this->bankReturn(['Response' => 'Declined', 'ProcReturnCode' => '05']);
        $p['Response'] = 'Approved';
        $p['ProcReturnCode'] = '00';
        $this->postBody($p);
        $r = $this->gateway->completePurchase()->send();

        $this->assertFalse($r->isVerified());
        $this->assertFalse($r->isSuccessful());
        $this->assertSame('pending', $r->getTransactionStatus());
    }

    public function testCompletePurchaseRejectsAmountMismatch(): void
    {
        $this->postBody($this->bankReturn());
        $r = $this->gateway->completePurchase(['transactionId' => 'ORD-1', 'amount' => '99.00'])->send();
        $this->assertFalse($r->isVerified());
    }

    public function testCompletePurchaseDeclined(): void
    {
        $this->postBody($this->bankReturn(['Response' => 'Declined', 'ProcReturnCode' => '51', 'ErrMsg' => 'Yetersiz bakiye']));
        $r = $this->gateway->acceptNotification()->send();

        $this->assertTrue($r->isVerified());
        $this->assertFalse($r->isSuccessful());
        $this->assertSame('Yetersiz bakiye', $r->getMessage());
        $this->assertSame('failed', $r->getTransactionStatus());
    }

    public function testRefundSuccess(): void
    {
        $this->setMockHttpResponse('RefundSuccess.txt');
        $r = $this->gateway->refund(['transactionId' => 'ORD-1', 'amount' => '5.00', 'currency' => 'TRY'])->send();

        $this->assertTrue($r->isSuccessful());
        $this->assertSame('24321ABC', $r->getTransactionReference());

        $sent = (string) $this->getMockClient()->getLastRequest()->getBody();
        $this->assertStringContainsString('<Type>Credit</Type>', $sent);
        $this->assertStringContainsString('<Total>5.00</Total>', $sent);
        $this->assertStringContainsString('<Name>ISBANKAPI</Name>', $sent);
        $this->assertSame(AbstractRequest::TEST_API, (string) $this->getMockClient()->getLastRequest()->getUri());
    }

    public function testRefundFailure(): void
    {
        $this->setMockHttpResponse('RefundFailure.txt');
        $r = $this->gateway->refund(['transactionId' => 'ORD-1', 'amount' => '5.00'])->send();

        $this->assertTrue($r->isReadable());
        $this->assertFalse($r->isSuccessful());
        $this->assertSame('Iade icin eslesen siparis bulunamadi', $r->getMessage());
    }

    public function testVoidAndStatusBodies(): void
    {
        $this->setMockHttpResponse('RefundSuccess.txt');
        $this->gateway->void(['transactionId' => 'ORD-1'])->send();
        $this->assertStringContainsString('<Type>Void</Type><OrderId>ORD-1</OrderId>', (string) $this->getMockClient()->getLastRequest()->getBody());

        $this->setMockHttpResponse('RefundSuccess.txt');
        $r = $this->gateway->fetchTransaction(['transactionId' => 'ORD-1'])->send();
        $this->assertStringContainsString('<Extra><ORDERSTATUS>QUERY</ORDERSTATUS></Extra>', (string) $this->getMockClient()->getLastRequest()->getBody());
        $this->assertSame(['SETTLEID' => '1'], $r->getExtra());
    }
}
