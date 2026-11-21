<?php

namespace Omnipay\NestPay\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\NotificationInterface;
use Omnipay\Common\Message\RequestInterface;

/**
 * 3D dönüşünün sonucu. isSuccessful(): imza geçerli + Response=Approved + ProcReturnCode=00.
 * isVerified() false ise alanlara güvenilmez (sahte ya da bozuk dönüş): sonuç kaydedilmemelidir.
 */
class CompletePurchaseResponse extends AbstractResponse implements NotificationInterface
{
    public function __construct(RequestInterface $request, $data, protected bool $verified)
    {
        parent::__construct($request, $data);
    }

    public function isVerified(): bool
    {
        return $this->verified;
    }

    public function isSuccessful(): bool
    {
        return $this->verified && ($this->data['Response'] ?? '') === 'Approved' && (string) ($this->data['ProcReturnCode'] ?? '') === '00';
    }

    public function getTransactionStatus(): string
    {
        if (!$this->verified) {
            return self::STATUS_PENDING;
        }

        return $this->isSuccessful() ? self::STATUS_COMPLETED : self::STATUS_FAILED;
    }

    /** oid (sipariş no) */
    public function getTransactionId(): ?string
    {
        return isset($this->data['oid']) ? (string) $this->data['oid'] : null;
    }

    /** Bankanın işlem numarası (TransId, yoksa onay kodu) */
    public function getTransactionReference(): ?string
    {
        $ref = (string) (($this->data['TransId'] ?? '') ?: ($this->data['AuthCode'] ?? ''));

        return $ref !== '' ? $ref : null;
    }

    public function getCode(): ?string
    {
        $c = (string) (($this->data['ErrCode'] ?? '') ?: ($this->data['ProcReturnCode'] ?? ''));

        return $c !== '' ? $c : null;
    }

    /** Ret nedeni (bankanın iletisi) ya da 3D doğrulama hatası */
    public function getMessage(): ?string
    {
        if (!$this->verified) {
            return 'Invalid signature';
        }
        if ($this->isSuccessful()) {
            return null;
        }
        $md = (string) ($this->data['mdStatus'] ?? '');
        if (in_array($md, ['0', '5', '6', '7', '8'], true) && ($this->data['Response'] ?? '') !== 'Declined') {
            return trim((string) ($this->data['mdErrorMsg'] ?? '')) ?: '3D Secure authentication failed';
        }

        return trim((string) ($this->data['ErrMsg'] ?? '')) ?: null;
    }

    public function getAmount(): ?string
    {
        return isset($this->data['amount']) ? (string) $this->data['amount'] : null;
    }

    public function getInstallment(): int
    {
        return max(1, (int) ($this->data['Instalment'] ?? $this->data['taksit'] ?? 0));
    }

    public function getMaskedCard(): ?string
    {
        $c = trim((string) (($this->data['MaskedPan'] ?? '') ?: ($this->data['maskedCreditCard'] ?? '')));

        return $c !== '' ? $c : null;
    }

    public function getAuthCode(): ?string
    {
        return ($this->data['AuthCode'] ?? '') !== '' ? (string) $this->data['AuthCode'] : null;
    }

    public function getHostReference(): ?string
    {
        return ($this->data['HostRefNum'] ?? '') !== '' ? (string) $this->data['HostRefNum'] : null;
    }

    public function getMdStatus(): ?string
    {
        return isset($this->data['mdStatus']) ? (string) $this->data['mdStatus'] : null;
    }
}
