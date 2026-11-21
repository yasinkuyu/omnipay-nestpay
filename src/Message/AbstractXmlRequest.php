<?php

namespace Omnipay\NestPay\Message;

/**
 * XML API (CC5Request): iade, iptal, provizyon kapama, sorgu. Sanal POS ekranında API yetkili bir kullanıcı gerekir
 * (username / password). Bağlantı hatasında Omnipay'in NetworkException'ı fırlar: sonuç belirsizdir, istek tekrarlanmamalı.
 */
abstract class AbstractXmlRequest extends AbstractRequest
{
    /** CC5Request içine eklenecek alanlar (Name / Password / ClientId hariç) */
    abstract protected function body(): array;

    public function getData(): array
    {
        $this->validate('clientId', 'username', 'password');

        return ['Name' => (string) $this->getUsername(), 'Password' => (string) $this->getPassword(), 'ClientId' => (string) $this->getClientId()]
            + $this->body();
    }

    public function sendData($data): XmlResponse
    {
        $res = $this->httpClient->request('POST', $this->getApiUrl(), ['Content-Type' => 'text/xml; charset=utf-8'], self::toXml($data));

        return $this->response = new XmlResponse($this, XmlResponse::parse((string) $res->getBody()));
    }

    public static function toXml(array $data, string $root = 'CC5Request'): string
    {
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $add = function (\DOMNode $parent, array $data) use ($doc, &$add) {
            foreach ($data as $k => $v) {
                $el = $doc->createElement((string) $k);
                is_array($v) ? $add($el, $v) : $el->appendChild($doc->createTextNode((string) $v));
                $parent->appendChild($el);
            }
        };
        $el = $doc->createElement($root);
        $add($el, $data);
        $doc->appendChild($el);

        return (string) $doc->saveXML();
    }
}
