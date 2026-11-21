<?php

namespace Omnipay\NestPay\Message;

use Omnipay\Common\Message\AbstractResponse;

/**
 * CC5Response. isSuccessful(): Response=Approved + ProcReturnCode=00. isReadable() false ise yanıt okunamadı:
 * işlem bankada olmuş da olabilir (iade / iptal için "belirsiz" sayılmalı, otomatik tekrar denenmemeli).
 */
class XmlResponse extends AbstractResponse
{
    public static function parse(string $body): ?array
    {
        if (trim($body) === '' || stripos($body, '<!DOCTYPE') !== false || stripos($body, '<!ENTITY') !== false) {
            return null;
        }
        $prev = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body, \SimpleXMLElement::class, LIBXML_NONET);
        libxml_use_internal_errors($prev);
        if ($xml === false) {
            return null;
        }
        $out = [];
        foreach ($xml->children() as $k => $v) {
            $out[$k] = $v->count() ? array_map('strval', iterator_to_array($v->children(), true)) : trim((string) $v);
        }

        return $out;
    }

    public function isReadable(): bool
    {
        return is_array($this->data);
    }

    public function isSuccessful(): bool
    {
        return is_array($this->data) && ($this->data['Response'] ?? '') === 'Approved' && (string) ($this->data['ProcReturnCode'] ?? '') === '00';
    }

    public function getTransactionReference(): ?string
    {
        return ($this->data['TransId'] ?? '') !== '' ? (string) $this->data['TransId'] : null;
    }

    public function getTransactionId(): ?string
    {
        return ($this->data['OrderId'] ?? '') !== '' ? (string) $this->data['OrderId'] : null;
    }

    public function getCode(): ?string
    {
        return ($this->data['ProcReturnCode'] ?? '') !== '' ? (string) $this->data['ProcReturnCode'] : null;
    }

    public function getMessage(): ?string
    {
        if (!is_array($this->data)) {
            return 'Unreadable response';
        }
        $m = html_entity_decode(trim((string) ($this->data['ErrMsg'] ?? '')), ENT_QUOTES | ENT_XML1, 'UTF-8');

        return $m !== '' ? $m : null;
    }

    /** Ek alanlar (Extra): sorguda TRANS_STAT, CAPTURE_AMT … */
    public function getExtra(): array
    {
        return is_array($this->data['Extra'] ?? null) ? $this->data['Extra'] : [];
    }
}
