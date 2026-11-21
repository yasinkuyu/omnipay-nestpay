# Değişiklikler

## 3.0.0

- Omnipay v3 (`omnipay/common` ^3.3), PHP 8.1+
- 3D Pay Hosting (varsayılan), 3D Pay, 3D modelleri; kart bilgisi sunucudan geçmez
- ver3 (SHA-512) imza; dönüş imzası, mağaza no, sipariş no ve tutar doğrulaması; noktalı alan adları için ham gövde ayrıştırma
- `completePurchase()`, `acceptNotification()`, `fetchTransaction()`, `refreshTime`, `notifyUrl` (callbackUrl)
- Güncel banka adresleri (QNB Finansbank eklendi); kart numarasıyla doğrudan (3D'siz) satış kaldırıldı
- Payten test ortamında doğrulandı
