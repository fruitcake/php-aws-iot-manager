<?php

namespace Fruitcake\AwsIot;

class CertificateHelper
{
    public static function generateKeypair()
    {
        $config = array(
            'digest_alg' => 'sha512',
            'private_key_bits' => 4096,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        );

        $res = openssl_pkey_new($config);

        openssl_pkey_export($res, $privateKey);
        $publicKey = openssl_pkey_get_details($res)['key'];

        return ['public' => $publicKey, 'private' => $privateKey];
    }

    public static function generateCsr($privateKey, $country, $organizationName, $commonName)
    {
        $dn = array(
            'countryName' => $country,
            'organizationName' => $organizationName,
            'commonName' => $commonName,
        );

        $privkey = openssl_pkey_get_private($privateKey);

        openssl_csr_export(openssl_csr_new($dn, $privkey), $csr);

        return $csr;
    }

}