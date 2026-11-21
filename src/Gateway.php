<?php

namespace Omnipay\NestPay;

use Omnipay\Common\AbstractGateway;
use Omnipay\NestPay\Message\AuthorizeRequest;
use Omnipay\NestPay\Message\CaptureRequest;
use Omnipay\NestPay\Message\CompletePurchaseRequest;
use Omnipay\NestPay\Message\FetchTransactionRequest;
use Omnipay\NestPay\Message\PurchaseRequest;
use Omnipay\NestPay\Message\RefundRequest;
use Omnipay\NestPay\Message\VoidRequest;

/**
 * NestPay (EST, Payten / Asseco) sanal POS.
 *
 * purchase()          3D ödeme formu (varsayılan 3D Pay Hosting: kart bankanın sayfasında girilir). Yanıt bir yönlendirmedir;
 *                     getRedirectUrl() / getRedirectData() POST formu olarak tarayıcıya verilir.
 * completePurchase()  bankanın dönüşü (okUrl / failUrl / callbackUrl): imza doğrulanır, sonuç okunur. acceptNotification() aynısı.
 * refund() / void()   XML API (CC5Request) ile iade / iptal; capture() ön provizyonu kapatır; fetchTransaction() durum sorgular.
 *
 * (c) Yasin Kuyu — https://github.com/yasinkuyu/omnipay-nestpay
 */
class Gateway extends AbstractGateway
{
    public function getName(): string
    {
        return 'NestPay';
    }

    public function getDefaultParameters(): array
    {
        return [
            'bank' => 'isbank',
            'clientId' => '',
            'storeKey' => '',
            'username' => '',
            'password' => '',
            'storeType' => '3d_pay_hosting',
            'lang' => 'tr',
            'testMode' => false,
        ];
    }

    /** Banka kodu: isbank, akbank, halkbank, ziraat, teb, finansbank, ingbank, sekerbank, anadolubank (Gateway::banks()) */
    public function getBank(): ?string { return $this->getParameter('bank'); }
    public function setBank($value): self { return $this->setParameter('bank', $value); }

    /** Listede olmayan banka için est3Dgate adresi (https) */
    public function getEndpoint(): ?string { return $this->getParameter('endpoint'); }
    public function setEndpoint($value): self { return $this->setParameter('endpoint', $value); }

    /** Mağaza numarası */
    public function getClientId(): ?string { return $this->getParameter('clientId'); }
    public function setClientId($value): self { return $this->setParameter('clientId', $value); }

    /** 3D güvenlik anahtarı (store key) */
    public function getStoreKey(): ?string { return $this->getParameter('storeKey'); }
    public function setStoreKey($value): self { return $this->setParameter('storeKey', $value); }

    /** XML API kullanıcısı (iade, iptal, sorgu) */
    public function getUsername(): ?string { return $this->getParameter('username'); }
    public function setUsername($value): self { return $this->setParameter('username', $value); }

    public function getPassword(): ?string { return $this->getParameter('password'); }
    public function setPassword($value): self { return $this->setParameter('password', $value); }

    /** 3d_pay_hosting | 3d_pay | 3d */
    public function getStoreType(): ?string { return $this->getParameter('storeType'); }
    public function setStoreType($value): self { return $this->setParameter('storeType', $value); }

    /** Banka sonuç ekranından dönüşe kadar saniye (bankanın varsayılanı 300) */
    public function getRefreshTime() { return $this->getParameter('refreshTime'); }
    public function setRefreshTime($value): self { return $this->setParameter('refreshTime', $value); }

    public function getLang(): ?string { return $this->getParameter('lang'); }
    public function setLang($value): self { return $this->setParameter('lang', $value); }

    /** Bilinen bankalar: kod => [3D adresi, XML API adresi] */
    public static function banks(): array
    {
        return Message\AbstractRequest::BANKS;
    }

    public function purchase(array $options = []): PurchaseRequest
    {
        return $this->createRequest(PurchaseRequest::class, $options);
    }

    public function completePurchase(array $options = []): CompletePurchaseRequest
    {
        return $this->createRequest(CompletePurchaseRequest::class, $options);
    }

    /** Ön provizyon (PreAuth) için 3D formu; capture() ile kapatılır */
    public function authorize(array $options = []): AuthorizeRequest
    {
        return $this->createRequest(AuthorizeRequest::class, $options);
    }

    public function completeAuthorize(array $options = []): CompletePurchaseRequest
    {
        return $this->createRequest(CompletePurchaseRequest::class, $options);
    }

    /** callbackUrl bildirimi: completePurchase ile aynı doğrulama */
    public function acceptNotification(array $options = []): CompletePurchaseRequest
    {
        return $this->createRequest(CompletePurchaseRequest::class, $options);
    }

    public function capture(array $options = []): CaptureRequest
    {
        return $this->createRequest(CaptureRequest::class, $options);
    }

    public function refund(array $options = []): RefundRequest
    {
        return $this->createRequest(RefundRequest::class, $options);
    }

    public function void(array $options = []): VoidRequest
    {
        return $this->createRequest(VoidRequest::class, $options);
    }

    public function fetchTransaction(array $options = []): FetchTransactionRequest
    {
        return $this->createRequest(FetchTransactionRequest::class, $options);
    }
}
