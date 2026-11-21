# Omnipay: NestPay

**[Omnipay](https://github.com/thephpleague/omnipay) driver for the NestPay (EST / Payten) virtual POS used by Turkish banks**

[![Latest Stable Version](https://poser.pugx.org/yasinkuyu/omnipay-nestpay/v/stable)](https://packagist.org/packages/yasinkuyu/omnipay-nestpay)
[![Total Downloads](https://poser.pugx.org/yasinkuyu/omnipay-nestpay/downloads)](https://packagist.org/packages/yasinkuyu/omnipay-nestpay)
[![License](https://poser.pugx.org/yasinkuyu/omnipay-nestpay/license)](https://packagist.org/packages/yasinkuyu/omnipay-nestpay)

[Türkçe](README.md) | **English**

## What does this package do?

It lets your shop take card payments. The customer goes to **the bank's secure payment page**, enters the card there,
completes 3D Secure and comes back to your site. Card data never touches your server, which keeps your PCI scope minimal.

```
Your site ──(signed form)──▶ Bank's payment page ──(3D Secure)──▶ Your site (return URL)
                                                  └──────────────▶ Your server (notify URL, optional)
```

**Supported banks:** İş Bankası, Akbank, Halkbank, Ziraat Bankası, TEB, QNB Finansbank, ING, Şekerbank, Anadolubank.
For any other bank running NestPay, pass the gateway URL it gives you as `endpoint`.

**Features**

| | |
|---|---|
| Payment | 3D Pay Hosting (default), 3D Pay, 3D |
| Installments | 2-12 (whatever your bank agreement allows) |
| Pre-authorization | Block with `authorize()`, collect with `capture()` |
| Refund / void | `refund()` (full or partial), `void()` (same day) |
| Status | `fetchTransaction()`: the order's status at the bank |
| Security | ver3 (SHA-512) signing; the return is checked for signature, store number, order number and amount |

## Installation

```bash
composer require yasinkuyu/omnipay-nestpay php-http/guzzle7-adapter
```

Requires PHP 8.1 or later. Any other PSR-18 HTTP client already in your project can replace `php-http/guzzle7-adapter`.

## What you need from your bank

| Setting | What is it? | Where to find it |
|---|---|---|
| `clientId` | Store number (Client ID) | Sent by the bank after your virtual POS application |
| `storeKey` | 3D security key (store key) | Virtual POS admin panel → Management → Security key. You set it yourself |
| `username`, `password` | API user (only for refund / void / status) | Create a new user **with API permission** in the virtual POS admin panel |
| `bank` | Bank code | `isbank`, `akbank`, `halkbank`, `ziraat`, `teb`, `finansbank`, `ingbank`, `sekerbank`, `anadolubank` |

## Quick start

A payment takes two pages: **the page that starts the payment** and **the return page** the bank sends the customer back to.

### 1. Set up the gateway

```php
use Omnipay\Omnipay;

$gateway = Omnipay::create('NestPay');
$gateway->initialize([
    'bank'     => 'isbank',
    'clientId' => '700655000200',
    'storeKey' => 'TRPS0200',
    'username' => 'ISBANKAPI',   // leave empty if you won't refund / void / query
    'password' => 'ISBANK07',
    'testMode' => true,          // false in production
]);
```

### 2. Start the payment (pay.php)

```php
$response = $gateway->purchase([
    'amount'        => '149.90',
    'currency'      => 'TRY',
    'transactionId' => 'ORD-10045',                       // your order number, unique per attempt
    'returnUrl'     => 'https://shop.com/pay-return.php',  // the bank sends the customer back here
    'notifyUrl'     => 'https://shop.com/pay-notify.php',  // optional, recommended
    'installment'   => 3,                                 // 1 or omit for a single payment
    'card'          => [                                  // NO card number; billing details only
        'email'            => 'ali@example.com',
        'billingFirstName' => 'Ali',
        'billingLastName'  => 'Kaya',
    ],
])->send();

$response->redirect(); // prints a form that takes the browser to the bank's payment page
```

To render the form on your own page instead: URL `$response->getRedirectUrl()`, fields `$response->getRedirectData()`,
method POST.

### 3. Verify the return (pay-return.php)

```php
$result = $gateway->completePurchase([
    'transactionId' => 'ORD-10045', // the order number from YOUR records
    'amount'        => '149.90',    // the amount from YOUR records
])->send();

if (!$result->isVerified()) {
    // Bad signature, or order number / amount differ: don't trust this return, don't mark the order paid.
} elseif ($result->isSuccessful()) {
    // Payment received.
    $result->getTransactionReference(); // bank transaction number
    $result->getInstallment();          // installment count
    $result->getMaskedCard();           // 454671***7894
} else {
    echo $result->getMessage();         // bank's decline reason or 3D Secure error
}
```

> **Why pass `transactionId` and `amount`?** The return comes through the customer's browser. The signature prevents
> forgery; comparing with the order number and amount from your own records prevents another order or a smaller amount
> from being accepted.

### 4. Notify URL (pay-notify.php, optional)

If the customer closes the browser before returning, the result also arrives here server-to-server. The bank expects
the text `Approved` as the response:

```php
$result = $gateway->acceptNotification(['transactionId' => $orderNo, 'amount' => $amount])->send();
if ($result->isVerified()) {
    // update the order with $result->isSuccessful() (make sure the same order isn't processed twice)
    echo 'Approved';
}
```

## Refund, void, status

```php
// Same day, before end-of-day: void (never shows on the customer's statement)
$gateway->void(['transactionId' => 'ORD-10045'])->send();

// Later days or a partial amount: refund
$gateway->refund(['transactionId' => 'ORD-10045', 'amount' => '50.00', 'currency' => 'TRY'])->send();

// Status at the bank
$status = $gateway->fetchTransaction(['transactionId' => 'ORD-10045'])->send();
$status->getExtra(); // ['ORDERSTATUS' => …, 'TRANS_STAT' => …]
```

Every response has `isSuccessful()` and `getMessage()`. **If `isReadable()` returns false**, the bank's response could
not be read: the operation may have gone through at the bank. Do not retry automatically (you might refund twice);
check the virtual POS panel instead.

## Pre-authorization

```php
$gateway->authorize([...])->send()->redirect();                         // same parameters as purchase()
$gateway->completeAuthorize(['transactionId' => …, 'amount' => …])->send();
$gateway->capture(['transactionId' => 'ORD-10045'])->send();             // collect (pass amount for a partial capture)
```

## Other settings

| Setting | Default | Description |
|---|---|---|
| `storeType` | `3d_pay_hosting` | `3d_pay` or `3d`: the card form is on your page and posts to the bank |
| `refreshTime` | bank default (300) | Seconds the bank's result screen waits before returning. A short value like `3` is recommended |
| `lang` | `tr` | Language of the bank page: `tr` or `en` |
| `endpoint` | | `https://…/fim/est3Dgate` URL for a bank not in the list |

## Test environment

With `testMode => true` requests go to Payten's test environment; no money is charged.

| | |
|---|---|
| Store number | `700655000200` |
| Store key | `TRPS0200` |
| API user | `ISBANKAPI` / `ISBANK07` |
| Test card | `4546 7112 3456 7894`, expiry 12/26, CVV `000` |

The test bank often declines with code "99"; the return is still signed, so you can test your verification flow.

## Troubleshooting

| Symptom | Cause |
|---|---|
| "Güvenlik kodu hatalı" (security code error) on the bank page | `storeKey` is wrong or doesn't match the one at the bank |
| `isVerified()` always false | Different `storeKey`, or your framework consumed the request body: pass the fields as `['fields' => $fields]` |
| Session is lost on return | The bank's return is a cross-site POST: don't rely on the session cookie on the return URL; find the record by order number |
| "Insufficent permissions" on refund | The API user lacks permission or the password is wrong |

PHP turns dots in form field names into underscores (`TDS2.authTimestamp`). The package reads the fields from the raw
request body with their names intact; you don't need to do anything.

## Upgrading from 2.x

3.0 is a rewrite for Omnipay v3:

- Direct (non-3D) sales with a card number were removed; payment is taken on the bank's page.
- `completePurchase()` and `acceptNotification()` were added; the package now verifies the return.
- `username` / `password` are only needed for refund, void and status.
- To keep using the old version (2.x): `composer require yasinkuyu/omnipay-nestpay:^2.0`

## Tests

```bash
composer install
vendor/bin/phpunit
```

## My other Omnipay packages

- **[Omnipay: GVP (Garanti BBVA)](https://github.com/yasinkuyu/omnipay-gvp)** — Garanti BBVA virtual POS; hosted payment page
- **[Omnipay: Paratika](https://github.com/yasinkuyu/omnipay-paratika)** — Paratika, VakıfPayS and ZiraatPay hosted payment page
- **[Omnipay: Paycell SDK](https://github.com/yasinkuyu/omnipay-paycell-sdk)** — Turkcell Paycell payment page (Web SDK)
- **[Omnipay: Paycell API](https://github.com/yasinkuyu/omnipay-paycell)** — Turkcell Paycell payment API
- **[Omnipay: iyzico](https://github.com/yasinkuyu/omnipay-iyzico)** — iyzico checkout form
- **[Omnipay: PayTR](https://github.com/yasinkuyu/omnipay-paytr)** — PayTR virtual POS
- **[Omnipay: Posnet](https://github.com/yasinkuyu/omnipay-posnet)** — Yapı Kredi Posnet virtual POS

## License

MIT — [Yasin Kuyu](https://github.com/yasinkuyu)
