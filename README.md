# Omnipay: NestPay

**NestPay / EST (Payten, Asseco) sanal POS sürücüsü — [Omnipay](https://github.com/thephpleague/omnipay) v3**

[![Latest Stable Version](https://poser.pugx.org/yasinkuyu/omnipay-nestpay/v/stable)](https://packagist.org/packages/yasinkuyu/omnipay-nestpay)
[![Total Downloads](https://poser.pugx.org/yasinkuyu/omnipay-nestpay/downloads)](https://packagist.org/packages/yasinkuyu/omnipay-nestpay)
[![License](https://poser.pugx.org/yasinkuyu/omnipay-nestpay/license)](https://packagist.org/packages/yasinkuyu/omnipay-nestpay)

NestPay altyapısını kullanan bankalar: **İş Bankası, Akbank, Halkbank, Ziraat Bankası, TEB, QNB Finansbank, ING, Şekerbank, Anadolubank** (listede olmayan banka için `endpoint` ile est3Dgate adresi verilebilir).

- **3D Pay Hosting** (varsayılan): kart bilgisi bankanın ödeme sayfasında girilir, sizin sunucunuzdan geçmez.
- `3d_pay` ve `3d` modelleri, taksit, ön provizyon (PreAuth / PostAuth)
- **ver3** imza (SHA-512), bankanın dönüşünün imza doğrulaması
- XML API ile iade, iptal, provizyon kapama ve sipariş durumu sorgusu

## Kurulum

```bash
composer require yasinkuyu/omnipay-nestpay:^3.0 php-http/guzzle7-adapter
```

PHP 8.1+ ve `omnipay/common` ^3.3 gerekir. 2.x sürümü (`omnipay/common` 2) `yasin` dalında duruyor.

## Kullanım

```php
use Omnipay\Omnipay;

$gateway = Omnipay::create('NestPay');
$gateway->initialize([
    'bank'      => 'isbank',        // isbank, akbank, halkbank, ziraat, teb, finansbank, ingbank, sekerbank, anadolubank
    'clientId'  => '700655000200',  // mağaza numarası
    'storeKey'  => 'TRPS0200',      // 3D güvenlik anahtarı
    'username'  => 'APIUSER',       // XML API kullanıcısı (iade / iptal / sorgu için)
    'password'  => 'APIPASS',
    'testMode'  => true,            // Payten test ortamı
]);
```

### 1. Ödemeyi başlatma

```php
$response = $gateway->purchase([
    'amount'        => '149.90',
    'currency'      => 'TRY',
    'transactionId' => 'SIP-10045',                    // oid
    'returnUrl'     => 'https://magaza.com/odeme/donus', // okUrl (failUrl verilmezse o da)
    'cancelUrl'     => 'https://magaza.com/odeme/donus', // failUrl (isteğe bağlı)
    'notifyUrl'     => 'https://magaza.com/odeme/bildirim', // callbackUrl (isteğe bağlı, önerilir)
    'installment'   => 3,
    'refreshTime'   => 3,                               // sonuç ekranında bekleme (sn)
    'card'          => ['email' => 'ali@ornek.com', 'billingFirstName' => 'Ali', 'billingLastName' => 'Kaya'],
])->send();

$response->redirect(); // bankaya otomatik POST eden form
// ya da kendi formunuz: $response->getRedirectUrl(), $response->getRedirectData()
```

### 2. Dönüşü doğrulama (okUrl / failUrl / callbackUrl)

```php
$result = $gateway->completePurchase([
    'transactionId' => 'SIP-10045', // isteğe bağlı: dönüşteki sipariş no ile karşılaştırılır
    'amount'        => '149.90',    // isteğe bağlı: dönüşteki tutarla karşılaştırılır
])->send();

if (!$result->isVerified()) {
    // imza tutmadı: sahte ya da bozuk dönüş, sonucu kaydetmeyin
} elseif ($result->isSuccessful()) {
    $result->getTransactionReference(); // TransId
    $result->getMaskedCard();           // 454671***7894
    $result->getInstallment();
} else {
    $result->getMessage();              // bankanın ret iletisi ya da 3D hatası
}
```

Bildirim adresinde `acceptNotification()` aynı doğrulamayı yapar; bankaya `Approved` metniyle yanıt verin.

Alanlar istek gövdesinden adları bozulmadan okunur (PHP `TDS2.authTimestamp` gibi adlardaki noktayı alt çizgiye çevirir ve imza doğrulaması bozulur). Çerçeveniz gövdeyi tükettiyse alanları `['fields' => $alanlar]` ile verin.

### İade, iptal, sorgu

```php
$gateway->void(['transactionId' => 'SIP-10045'])->send();                         // aynı gün, gün sonundan önce
$gateway->refund(['transactionId' => 'SIP-10045', 'amount' => '50.00'])->send(); // kısmi / sonraki gün
$gateway->capture(['transactionId' => 'SIP-10045'])->send();                     // ön provizyonu kapat
$status = $gateway->fetchTransaction(['transactionId' => 'SIP-10045'])->send();
$status->getExtra(); // ORDERSTATUS, TRANS_STAT …
```

`isReadable()` false ise bankanın yanıtı okunamadı: işlem gerçekleşmiş olabilir, otomatik tekrar denemeyin.

## Test ortamı

Payten test ortamı (`testMode => true`): mağaza no `700655000200`, anahtar `TRPS0200`, API kullanıcısı `ISBANKAPI` / `ISBANK07`, kart `4546 7112 3456 7894`, 12/26, CVV `000`.

## Testler

```bash
composer install && vendor/bin/phpunit
```

## Lisans

MIT — Yasin Kuyu
