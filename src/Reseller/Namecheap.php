<?php
/**
 * @link https://github.com/SilvioPorcellana/yii2-domains-ssl-reseller
 * @copyright Copyright (c) 2018 The Maven System
 * @license https://opensource.org/licenses/BSD-3-Clause
 */

namespace TheMavenSystem\DomainsSSLReseller\Reseller;

use TheMavenSystem\DomainsSSLReseller\Http\RequestManager;
use TheMavenSystem\DomainsSSLReseller\ResellerInterface;

class Namecheap implements ResellerInterface
{
    private $_api_user;
    private $_api_key;
    private $_api_url;
    private $_request_manager;

    /**
     * Namecheap constructor. Needs an api_user and api_key which will then be used with the API calls, and an optional $sanbox param
     *
     * @param string $api_user
     * @param string $api_key
     * @param bool $sandbox
     */
    public function __construct($api_user, $api_key, $sandbox = false)
    {
        if ($api_user)
        {
            $this->_api_user = $api_user;
        }
        if ($api_key) {
            $this->_api_key = $api_key;
        }

        $this->_request_manager = new RequestManager();

        if ($sandbox) {
            $this->_api_url = "https://api.sandbox.namecheap.com/xml.response";
        } else {
            $this->_api_url = "https://api.namecheap.com/xml.response";
        }
    }



    /**
     * Domains stuff
     */

    public function domainCheck($domains)
    {
        // TODO: Implement domainCheck() method.
    }


    public function domainRegister($domain, $data)
    {
        // TODO: Implement domainRegister() method.
    }


    public function domainInfo($domain)
    {
        // TODO: Implement domainInfo() method.
    }


    public function domainRenew($domain)
    {
        // TODO: Implement domainRenew() method.
    }



    /**
     * SSL stuff
     */

    public function sslCreate($type, $domains, $email, array $data, $dcv = 'http', $csr = '', $key = '', $webservertype = 'apacheopenssl')
    {
        /* first step - "namecheap create" */
        $data['command'] = "namecheap.ssl.create";
        $data['type'] = $type;

        $request = self::_map_to($data);
        $request = self::_add_global_fields($request);

        $response = $this->getRequestManager()->sendRequest('GET', $this->_api_url, false, $request);
    }


    public function sslCheck($id)
    {
        // TODO: Implement sslCheck() method.
    }



    /**
     * User stuff
     */

    public function userBalance()
    {
        // TODO: Implement userBalance() method.
    }

    public function userDomainsList()
    {
        // TODO: Implement userDomainsList() method.
    }

    public function userSslList()
    {
        // TODO: Implement userSslList() method.
    }





    private function _add_global_fields(array $fields)
    {
        $common_fields = [
            'ApiUser' => $this->_api_user,
            'ApiKey' => $this->_api_key,
            'UserName' => $this->_api_user,
            'ClientIp' => self::_clientIP(),
        ];

        array_push($fields, $common_fields);
        return $fields;
    }


    private static function _map_to(array $fields)
    {
        $_return = [];

        $_mapping = [
            'email' => 'AdminEmailAddress',
            'organization_name' => 'AdminOrganizationName',
            'organization_unit_name' => 'OrganizationDepartment',
            'country' => 'AdminCountry',
            'state' => 'AdminStateProvince',
            'city' => 'AdminCity',
            'address' => 'AdminAddress1',
            'zip' => 'AdminPostalCode',
            'command' => 'Command',
        ];

        $_mapping_maxlenght = [
            'organization_name' => 255,
            'organization_unit_name' => 255,
            'country' => 2,
            'state' => 255,
            'city' => 255,
            'address' => 255,
            'zip' => 255,
        ];

        // type
        $fields['type'] = self::_map_type($fields['type']);


        foreach ($fields as $field => $value) {
            $_return[$_mapping[$field] ? $_mapping[$field] : $field] = $_mapping_maxlenght[$field] ? substr($value, 0, $_mapping_maxlenght[$field]) : $value;
        }

        return $_return;
    }


    private static function _map_type($type)
    {
        $_mapping = [
            'single' => 'PositiveSSL',
            'wildcard' => 'PositiveSSL Wildcard',
            'multidomain' => 'PositiveSSL Multi Domain',
        ];

        $_type = $_mapping[$type];
        if (! $_type) {
            throw new \InvalidArgumentException('wrong ssl certificate type: "' . $type . '"');
        }

        return $_type;
    }


    private static function _clientIP()
    {
        return $_SERVER['SERVER_ADDR'];
    }


}