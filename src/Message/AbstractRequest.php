<?php

namespace Omnipay\NestPay\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Message\AbstractRequest as BaseAbstractRequest;

/**
 * Ortak parametreler, banka adresleri ve ver3 imzası.
 */
abstract class AbstractRequest extends BaseAbstractRequest
{
    public const TEST_GATE = 'https://entegrasyon.asseco-see.com.tr/fim/est3Dgate';
    public const TEST_API = 'https://entegrasyon.asseco-see.com.tr/fim/api';

    /** kod => [3D adresi, XML API adresi] */
    public const BANKS = [
        'isbank' => ['https://sanalpos.isbank.com.tr/fim/est3Dgate', 'https://sanalpos.isbank.com.tr/fim/api'],
        'akbank' => ['https://www.sanalakpos.com/fim/est3Dgate', 'https://www.sanalakpos.com/fim/api'],
        'halkbank' => ['https://sanalpos.halkbank.com.tr/fim/est3Dgate', 'https://sanalpos.halkbank.com.tr/fim/api'],
        'ziraat' => ['https://sanalpos2.ziraatbank.com.tr/fim/est3Dgate', 'https://sanalpos2.ziraatbank.com.tr/fim/api'],
        'teb' => ['https://sanalpos.teb.com.tr/fim/est3Dgate', 'https://sanalpos.teb.com.tr/fim/api'],
        'finansbank' => ['https://www.fbwebpos.com/fim/est3Dgate', 'https://www.fbwebpos.com/fim/api'],
        'ingbank' => ['https://sanalpos.ingbank.com.tr/fim/est3Dgate', 'https://sanalpos.ingbank.com.tr/fim/api'],
        'sekerbank' => ['https://sanalpos.sekerbank.com.tr/fim/est3Dgate', 'https://sanalpos.sekerbank.com.tr/fim/api'],
        'anadolubank' => ['https://anadolusanalpos.est.com.tr/fim/est3Dgate', 'https://anadolusanalpos.est.com.tr/fim/api'],
    ];

    public const CURRENCIES = ['TRY' => '949', 'TL' => '949', 'USD' => '840', 'EUR' => '978', 'GBP' => '826', 'JPY' => '392', 'RUB' => '643'];

    public function getBank() { return $this->getParameter('bank'); }
    public function setBank($value) { return $this->setParameter('bank', $value); }

    public function getEndpoint() { return $this->getParameter('endpoint'); }
    public function setEndpoint($value) { return $this->setParameter('endpoint', $value); }

    public function getClientId() { return $this->getParameter('clientId'); }
    public function setClientId($value) { return $this->setParameter('clientId', $value); }

    public function getStoreKey() { return $this->getParameter('storeKey'); }
    public function setStoreKey($value) { return $this->setParameter('storeKey', $value); }

    public function getUsername() { return $this->getParameter('username'); }
    public function setUsername($value) { return $this->setParameter('username', $value); }

    public function getPassword() { return $this->getParameter('password'); }
    public function setPassword($value) { return $this->setParameter('password', $value); }

    public function getStoreType() { return $this->getParameter('storeType'); }
    public function setStoreType($value) { return $this->setParameter('storeType', $value); }

    public function getLang() { return $this->getParameter('lang'); }
    public function setLang($value) { return $this->setParameter('lang', $value); }

    /** Banka sonuç ekranından dönüşe kadar saniye (bankanın varsayılanı 300) */
    public function getRefreshTime() { return $this->getParameter('refreshTime'); }
    public function setRefreshTime($value) { return $this->setParameter('refreshTime', $value); }

    /** Taksit sayısı (1 ya da boş = tek çekim) */
    public function getInstallment() { return $this->getParameter('installment'); }
    public function setInstallment($value) { return $this->setParameter('installment', $value); }

    /** Bankanın 3D formu adresi */
    public function getGatewayUrl(): string
    {
        if ($this->getTestMode()) {
            return self::TEST_GATE;
        }
        $custom = trim((string) $this->getEndpoint());
        if ($custom !== '') {
            if (!str_starts_with(strtolower($custom), 'https://') || !filter_var($custom, FILTER_VALIDATE_URL)) {
                throw new InvalidRequestException('NestPay endpoint must be an https URL.');
            }

            return $custom;
        }
        $bank = strtolower((string) $this->getBank());
        if (!isset(self::BANKS[$bank])) {
            throw new InvalidRequestException('Unknown NestPay bank: ' . $bank);
        }

        return self::BANKS[$bank][0];
    }

    /** Bankanın XML API adresi (…/fim/est3Dgate → …/fim/api) */
    public function getApiUrl(): string
    {
        if ($this->getTestMode()) {
            return self::TEST_API;
        }
        $bank = strtolower((string) $this->getBank());
        if (trim((string) $this->getEndpoint()) === '' && isset(self::BANKS[$bank])) {
            return self::BANKS[$bank][1];
        }

        return (string) preg_replace('#/fim/est3dgate$#i', '/fim/api', $this->getGatewayUrl());
    }

    /** ISO 4217 sayısal kod */
    public function getCurrencyNumeric(): string
    {
        $c = strtoupper((string) ($this->getCurrency() ?: 'TRY'));
        if (!isset(self::CURRENCIES[$c])) {
            throw new InvalidRequestException('Unsupported currency: ' . $c);
        }

        return self::CURRENCIES[$c];
    }

    /**
     * hashAlgorithm=ver3 imzası: HASH ve encoding dışındaki alanlar ada göre doğal sırada (büyük/küçük harf duyarsız),
     * değerlerde "\" → "\\" ve "|" → "\|"; "|" ile birleşir, sonuna store key eklenir; base64(SHA-512).
     * Bankanın dönüşü de aynı kuralla imzalıdır. nationalidno (Ziraat dönüşlerinde gelebilir) imzaya girmez.
     */
    public static function sign(array $fields, string $storeKey): string
    {
        $keys = array_keys($fields);
        natcasesort($keys);
        $s = '';
        foreach ($keys as $k) {
            if (in_array(strtolower((string) $k), ['hash', 'encoding', 'nationalidno'], true) || is_array($fields[$k])) {
                continue;
            }
            $s .= self::escape((string) $fields[$k]) . '|';
        }

        return base64_encode(hash('sha512', $s . self::escape($storeKey), true));
    }

    private static function escape(string $v): string
    {
        return str_replace('|', '\\|', str_replace('\\', '\\\\', $v));
    }
}
