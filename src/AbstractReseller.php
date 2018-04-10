<?php
/**
 * Created by PhpStorm.
 * User: silvioporcellana
 * Date: 09/04/18
 * Time: 21:38
 */

namespace TheMavenSystem\DomainsSSLReseller;

use yii\base\Component;
use yii\httpclient\Client;

abstract class AbstractReseller implements ResellerInterface
{
    const SSL_STATUS_ACTIVATING = 'activating';
    const SSL_STATUS_VALIDATING = 'validating';
    const SSL_STATUS_ERROR = 'error';
    const SSL_STATUS_ACTIVE = 'active';

    protected $_httpClient;

    abstract function domainCheck($domains);
    abstract function domainRegister($domain, $data);
    abstract function domainInfo($domain);
    abstract function domainRenew($domain);

    abstract function sslCheck($id);
    abstract function sslCreate($type, $domain, $email, array $data, $dcv = 'http', $csr = '', $private_key = '', $webservertype = 'apacheopenssl', $approver_email = '');
    abstract function sslApproverEmails($domain);

    abstract function userBalance();
    abstract function userDomainsList();
    abstract function userSslList();



    protected function getHttpClient()
    {
        if (!is_object($this->_httpClient)) {
            $this->_httpClient = Yii::createObject([
                'class' => Client::className(),
            ]);
        }
        return $this->_httpClient;
    }


    protected static function _check_required_data(array $fields)
    {
        $_required_fields = [
            'email',
            'organization_name',
            'organization_unit_name',
            'country',
            'state',
            'city',
            'address',
            'zip',
            'command',
        ];

        foreach ($_required_fields as $_required_field)
        {
            if (! $fields[$_required_field])
            {
                return false;
            }
        }

        return true;
    }


    protected static function _clientIP()
    {
        return $_SERVER['SERVER_ADDR'];
    }


    protected static function _create_key()
    {
        if (!extension_loaded('openssl')) {
            /* TODO: fallback */
            throw new \Exception("If you don't provide a CSR/private key the OpenSSL extension needs to be installed");
        }

        $private_key = openssl_pkey_new(array(
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ));
        return $private_key;
    }


    protected static function _create_csr($domain, $email, $data, $private_key)
    {
        if (!extension_loaded('openssl')) {
            /* TODO: fallback */
            throw new \Exception("If you don't provide a CSR/private key the OpenSSL extension needs to be installed");
        }

        $dn = array(
            "countryName" => $data['country'],
            "stateOrProvinceName" => $data['state'],
            "localityName" => $data['city'],
            "organizationName" => $data['organization_name'],
            "organizationalUnitName" => $data['organization_unit_name'],
            "commonName" => $domain,
            "emailAddress" => $email,
        );

        $csr = openssl_csr_new($dn, $private_key, array('digest_alg' => 'sha256'));
        return $csr;
    }

}