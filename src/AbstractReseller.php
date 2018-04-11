<?php
/**
 * Created by PhpStorm.
 * User: silvioporcellana
 * Date: 09/04/18
 * Time: 21:38
 */

namespace TheMavenSystem\DomainsSSLReseller;

use Yii;
use \yii\base\Component;
use \yii\httpclient\Client;

abstract class AbstractReseller implements ResellerInterface
{
    const SSL_STATUS_ACTIVATING = 'activating';
    const SSL_STATUS_VALIDATING = 'validating';
    const SSL_STATUS_ERROR = 'error';
    const SSL_STATUS_ACTIVE = 'active';


    protected $_api_user;
    protected $_api_key;
    protected $_api_url;
    protected $_sandbox;

    protected $_httpClient;

    abstract function domainCheck($domains);
    abstract function domainRegister($domain, $data);
    abstract function domainInfo($domain);
    abstract function domainRenew($domain);

    abstract function sslList($search = '');
    abstract function sslCheck($id, $domain = '', $dcv = 'http', $approver_email = '');
    abstract function sslCreate($type);
    abstract function sslActivate($certificate_id, $domain, $email, array $data, $dcv = 'http', $csr = '', $private_key = '', $webservertype = 'apacheopenssl', $approver_email = '');
    abstract function sslReissue($certificate_id, $domain, $email, array $data, $dcv = 'http', $csr = '', $private_key = '', $webservertype = 'apacheopenssl', $approver_email = '');
    abstract function sslApproverEmails($domain);

    abstract function userBalance();
    abstract function userDomainsList();
    abstract function userSslList();

    abstract function doRequest($data, $method = "GET");


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
            'organization_name',
            'organization_unit_name',
            'country',
            'state',
            'city',
            'address',
            'zip',
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


    protected static function _clientIP($sandbox)
    {
        if ($sandbox)
        {
            return "93.35.147.96";
        }
        else
        {
            return $_SERVER['SERVER_ADDR'];
        }
    }


    protected static function _create_key()
    {
        if (!extension_loaded('openssl')) {
            /* TODO: fallback */
            throw new \Exception("If you don't provide a CSR/private key the OpenSSL extension needs to be installed");
        }

        $private_key_res = openssl_pkey_new(array(
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ));
        openssl_pkey_export($private_key_res, $private_key);
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

        $csr_res = openssl_csr_new($dn, $private_key, array('digest_alg' => 'sha256'));
        openssl_csr_export($csr_res, $csr);
        return $csr;
    }

}