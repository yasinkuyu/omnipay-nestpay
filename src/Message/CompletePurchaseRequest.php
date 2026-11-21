<?php

namespace Omnipay\NestPay\Message;

/**
 * Bankanın dönüşü (okUrl / failUrl tarayıcı POST'u ya da callbackUrl bildirimi). Ağ isteği yapmaz; imzayı doğrular.
 *
 * Alanlar istek gövdesinden adları bozulmadan okunur: PHP form adlarındaki noktayı alt çizgiye çevirir (TDS2.authTimestamp,
 * EXTRA.TRXDATE …) ve imza alan adlarıyla sıralandığı için doğrulama bozulur. Çerçeve gövdeyi zaten tükettiyse alanlar
 * setFields() ile verilebilir.
 *
 * İsteğe bağlı denetim: amount ve transactionId verilirse dönüşteki tutar / sipariş no ile karşılaştırılır.
 */
class CompletePurchaseRequest extends AbstractRequest
{
    public function getFields() { return $this->getParameter('fields'); }
    public function setFields($value) { return $this->setParameter('fields', $value); }

    public function getData(): array
    {
        $this->validate('clientId', 'storeKey');

        return is_array($this->getFields()) ? $this->getFields() : self::parseBody($this->httpRequest);
    }

    public function sendData($data): CompletePurchaseResponse
    {
        $given = (string) ($data['HASH'] ?? $data['hash'] ?? '');
        $valid = $given !== '' && hash_equals(self::sign($data, (string) $this->getStoreKey()), $given)
            && (string) ($data['clientid'] ?? '') === (string) $this->getClientId();

        if ($valid && $this->getTransactionId() !== null && (string) ($data['oid'] ?? '') !== (string) $this->getTransactionId()) {
            $valid = false;
        }
        if ($valid && $this->getParameter('amount') !== null && isset($data['amount'])
            && abs((float) $data['amount'] - (float) $this->getAmount()) >= 0.01) {
            $valid = false;
        }

        return $this->response = new CompletePurchaseResponse($this, $data, $valid);
    }

    /** @param \Symfony\Component\HttpFoundation\Request $request */
    public static function parseBody($request): array
    {
        $body = (string) $request->getContent();
        if ($body === '' || !str_contains($body, '=')) {
            return $request->request->all();
        }
        $out = [];
        foreach (explode('&', $body) as $pair) {
            if ($pair === '') {
                continue;
            }
            [$k, $v] = array_pad(explode('=', $pair, 2), 2, '');
            $out[urldecode($k)] = urldecode($v);
        }

        return $out;
    }
}
