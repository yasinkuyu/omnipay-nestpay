# Omnipay: NestPay

**Türkiye bankalarının NestPay (EST / Payten) sanal POS'u için [Omnipay](https://github.com/thephpleague/omnipay) sürücüsü**

[![Latest Stable Version](https://poser.pugx.org/yasinkuyu/omnipay-nestpay/v/stable)](https://packagist.org/packages/yasinkuyu/omnipay-nestpay)
[![Total Downloads](https://poser.pugx.org/yasinkuyu/omnipay-nestpay/downloads)](https://packagist.org/packages/yasinkuyu/omnipay-nestpay)
[![License](https://poser.pugx.org/yasinkuyu/omnipay-nestpay/license)](https://packagist.org/packages/yasinkuyu/omnipay-nestpay)

**Türkçe** | [English](README_EN.md)

## Bu paket ne yapar?

Mağazanızda kartla ödeme almanızı sağlar. Müşteri **bankanın güvenli ödeme sayfasına** gider, kartını orada girer,
3D Secure doğrulamasını yapar ve sitenize geri döner. Kart bilgisi sizin sunucunuzdan hiç geçmez, bu yüzden PCI
yükünüz en azdır.

```
Siteniz ──(imzalı form)──▶ Bankanın ödeme sayfası ──(3D Secure)──▶ Siteniz (dönüş adresi)
                                                    └──────────────▶ Sunucunuz (bildirim adresi, isteğe bağlı)
```

**Desteklenen bankalar:** İş Bankası, Akbank, Halkbank, Ziraat Bankası, TEB, QNB Finansbank, ING, Şekerbank,
Anadolubank. Listede olmayan ama NestPay kullanan bir banka için bankanın verdiği adresi `endpoint` ile yazabilirsiniz.

**Özellikler**

| | |
|---|---|
| Ödeme | 3D Pay Hosting (varsayılan), 3D Pay, 3D |
| Taksit | 2-12 taksit (bankanızla anlaştığınız kadar) |
| Ön provizyon | `authorize()` ile tutarı bloke et, `capture()` ile çek |
| İade / iptal | `refund()` (tam ya da kısmi), `void()` (aynı gün) |
| Sorgu | `fetchTransaction()` ile siparişin bankadaki durumu |
| Güvenlik | ver3 (SHA-512) imza; dönüşte imza, mağaza no, sipariş no ve tutar denetimi |

## Kurulum

```bash
composer require yasinkuyu/omnipay-nestpay php-http/guzzle7-adapter
```

PHP 8.1 ya da üstü gerekir. `php-http/guzzle7-adapter` yerine projenizde zaten olan başka bir PSR-18 HTTP istemcisi
de kullanılabilir.

## Bankadan almanız gereken bilgiler

| Ayar | Nedir? | Nereden bulunur? |
|---|---|---|
| `clientId` | Mağaza numarası (Client ID) | Banka sanal POS başvurusu sonrası gönderilen bilgiler |
| `storeKey` | 3D güvenlik anahtarı (Store key) | Sanal POS yönetim ekranı → Yönetim → Güvenlik anahtarı. Kendiniz belirlersiniz |
| `username`, `password` | API kullanıcısı (yalnız iade / iptal / sorgu için) | Sanal POS yönetim ekranında **API yetkili** yeni bir kullanıcı açın |
| `bank` | Banka kodu | `isbank`, `akbank`, `halkbank`, `ziraat`, `teb`, `finansbank`, `ingbank`, `sekerbank`, `anadolubank` |

## Hızlı başlangıç

Bir ödeme iki sayfadan oluşur: **ödemeyi başlatan sayfa** ve bankanın müşteriyi geri gönderdiği **dönüş sayfası**.

### 1. Gateway'i hazırlayın

```php
use Omnipay\Omnipay;

$gateway = Omnipay::create('NestPay');
$gateway->initialize([
    'bank'     => 'isbank',
    'clientId' => '700655000200',
    'storeKey' => 'TRPS0200',
    'username' => 'ISBANKAPI',   // iade / iptal / sorgu yapmayacaksanız boş bırakabilirsiniz
    'password' => 'ISBANK07',
    'testMode' => true,          // canlıya geçerken false
]);
```

### 2. Ödemeyi başlatın (odeme.php)

```php
$response = $gateway->purchase([
    'amount'        => '149.90',
    'currency'      => 'TRY',
    'transactionId' => 'SIP-10045',                          // sizin sipariş numaranız, her denemede benzersiz
    'returnUrl'     => 'https://magaza.com/odeme-donus.php', // banka müşteriyi buraya geri gönderir
    'notifyUrl'     => 'https://magaza.com/odeme-bildirim.php', // isteğe bağlı, önerilir
    'installment'   => 3,                                    // tek çekim için 1 ya da hiç yazmayın
    'card'          => [                                     // kart numarası YOK; yalnız fatura bilgisi
        'email'            => 'ali@ornek.com',
        'billingFirstName' => 'Ali',
        'billingLastName'  => 'Kaya',
    ],
])->send();

$response->redirect(); // tarayıcıyı bankanın ödeme sayfasına götüren formu basar ve gönderir
```

Kendi sayfanızda göstermek isterseniz formu siz de kurabilirsiniz: adres `$response->getRedirectUrl()`, alanlar
`$response->getRedirectData()`, yöntem POST.

### 3. Dönüşü doğrulayın (odeme-donus.php)

```php
$result = $gateway->completePurchase([
    'transactionId' => 'SIP-10045', // KENDİ kaydınızdaki sipariş no
    'amount'        => '149.90',    // KENDİ kaydınızdaki tutar
])->send();

if (!$result->isVerified()) {
    // İmza tutmadı ya da sipariş no / tutar farklı: bu dönüşe güvenmeyin, siparişi ödenmiş saymayın.
} elseif ($result->isSuccessful()) {
    // Ödeme alındı.
    $result->getTransactionReference(); // bankanın işlem numarası
    $result->getInstallment();          // taksit sayısı
    $result->getMaskedCard();           // 454671***7894
} else {
    echo $result->getMessage();         // bankanın ret nedeni ya da 3D Secure hatası
}
```

> **Neden `transactionId` ve `amount` veriyoruz?** Dönüş, müşterinin tarayıcısından gelir. İmza sahteciliği önler;
> sizin kaydınızdaki sipariş no ve tutarla karşılaştırmak da başka bir siparişin ya da eksik tutarın kabul edilmesini önler.

### 4. Bildirim adresi (odeme-bildirim.php, isteğe bağlı)

Müşteri dönüşten önce tarayıcıyı kapatırsa sonuç bu adrese sunucudan sunucuya da gelir. Banka yanıt olarak
`Approved` metnini bekler:

```php
$result = $gateway->acceptNotification(['transactionId' => $siparisNo, 'amount' => $tutar])->send();
if ($result->isVerified()) {
    // $result->isSuccessful() ile siparişi güncelleyin (aynı sipariş iki kez işlenmesin)
    echo 'Approved';
}
```

## İade, iptal, sorgu

```php
// Aynı gün, gün sonundan önce: iptal (müşterinin ekstresinde hiç görünmez)
$gateway->void(['transactionId' => 'SIP-10045'])->send();

// Sonraki günler ya da kısmi tutar: iade
$gateway->refund(['transactionId' => 'SIP-10045', 'amount' => '50.00', 'currency' => 'TRY'])->send();

// Bankadaki durum
$status = $gateway->fetchTransaction(['transactionId' => 'SIP-10045'])->send();
$status->getExtra(); // ['ORDERSTATUS' => …, 'TRANS_STAT' => …]
```

Her yanıtta `isSuccessful()` ve `getMessage()` vardır. **`isReadable()` false dönerse** bankanın yanıtı okunamamıştır:
işlem bankada gerçekleşmiş olabilir. Otomatik olarak yeniden denemeyin (iki kez iade olabilir); sanal POS ekranından
kontrol edin.

## Ön provizyon

```php
$gateway->authorize([...])->send()->redirect();                        // purchase() ile aynı parametreler
$gateway->completeAuthorize(['transactionId' => …, 'amount' => …])->send();
$gateway->capture(['transactionId' => 'SIP-10045'])->send();            // tutarı çek (amount verirseniz o kadar)
```

## Diğer ayarlar

| Ayar | Varsayılan | Açıklama |
|---|---|---|
| `storeType` | `3d_pay_hosting` | `3d_pay` ya da `3d`: kart formu sizin sayfanızda, banka adresine POST edilir |
| `refreshTime` | bankanın varsayılanı (300) | Banka sonuç ekranında kaç saniye bekleyip size dönsün. `3` gibi kısa bir değer önerilir |
| `lang` | `tr` | Banka sayfasının dili: `tr` ya da `en` |
| `endpoint` | | Listede olmayan banka için `https://…/fim/est3Dgate` adresi |

## Test ortamı

`testMode => true` iken istekler Payten'in test ortamına gider, para çekilmez.

| | |
|---|---|
| Mağaza no | `700655000200` |
| Store key | `TRPS0200` |
| API kullanıcısı | `ISBANKAPI` / `ISBANK07` |
| Test kartı | `4546 7112 3456 7894`, son kullanma 12/26, CVV `000` |

Test bankası çekimi çoğu zaman "99" koduyla reddeder; bu durumda bile dönüş imzalıdır ve doğrulama akışınızı
deneyebilirsiniz.

## Sık karşılaşılan durumlar

| Belirti | Neden |
|---|---|
| Banka sayfasında "Güvenlik kodu hatalı" | `storeKey` yanlış ya da bankadaki ile aynı değil |
| `isVerified()` hep false | `storeKey` farklı, ya da çerçeveniz istek gövdesini tükettiyse alanları `['fields' => $alanlar]` ile verin |
| Dönüşte oturum düşüyor | Banka dönüşü siteler arası POST'tur: dönüş adresinde oturum çerezine güvenmeyin, sipariş no ile kaydı bulun |
| İadede "Insufficent permissions" | API kullanıcısının yetkisi yok ya da şifre yanlış |

PHP, form alan adlarındaki noktayı alt çizgiye çevirir (`TDS2.authTimestamp`). Paket alanları istek gövdesinden
adları bozulmadan okur, ayrıca bir şey yapmanız gerekmez.

## 2.x'ten geçiş

3.0, Omnipay v3 için baştan yazıldı:

- Kart numarasıyla doğrudan (3D'siz) satış kaldırıldı; ödeme bankanın sayfasında alınır.
- `completePurchase()` ve `acceptNotification()` eklendi; dönüşü artık paket doğrular.
- `username` / `password` yalnız iade, iptal ve sorgu için gerekir.
- Eski sürümü (2.x) kullanmaya devam etmek isterseniz: `composer require yasinkuyu/omnipay-nestpay:^2.0`

## Testler

```bash
composer install
vendor/bin/phpunit
```

## Diğer Omnipay paketleri

- **[Omnipay: GVP (Garanti BBVA)](https://github.com/yasinkuyu/omnipay-gvp)** — Garanti BBVA sanal POS'u; Ortak Ödeme Sayfası
- **[Omnipay: QNB](https://github.com/yasinkuyu/omnipay-qnb)** — QNB sanal POS'u (PayFor); ortak ödeme sayfası
- **[Omnipay: Paratika](https://github.com/yasinkuyu/omnipay-paratika)** — Paratika, VakıfPayS ve ZiraatPay ortak ödeme sayfası
- **[Omnipay: Akbank](https://github.com/yasinkuyu/omnipay-akbank)** — Akbank sanal POS'u (yeni altyapı); ortak ödeme sayfası
- **[Omnipay: Paycell SDK](https://github.com/yasinkuyu/omnipay-paycell-sdk)** — Turkcell Paycell ödeme sayfası (Web SDK)
- **[Omnipay: Paycell API](https://github.com/yasinkuyu/omnipay-paycell)** — Turkcell Paycell ödeme API'si
- **[Omnipay: iyzico](https://github.com/yasinkuyu/omnipay-iyzico)** — iyzico ödeme formu
- **[Omnipay: PayTR](https://github.com/yasinkuyu/omnipay-paytr)** — PayTR sanal POS
- **[Omnipay: Posnet](https://github.com/yasinkuyu/omnipay-posnet)** — Yapı Kredi Posnet sanal POS'u

## Lisans

MIT — [Yasin Kuyu](https://github.com/yasinkuyu)
