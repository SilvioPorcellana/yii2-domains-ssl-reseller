<?php
/**
 * @link https://github.com/SilvioPorcellana/yii2-domains-ssl-reseller
 * @copyright Copyright (c) 2018 The Maven System
 * @license https://opensource.org/licenses/BSD-3-Clause
 * @see https://www.namecheap.com/support/api/methods/
 */

namespace TheMavenSystem\DomainsSSLReseller\Reseller;

use TheMavenSystem\DomainsSSLReseller\AbstractReseller;

class Namecheap extends AbstractReseller
{
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

        if ($sandbox) {
            $this->_sandbox = true;
            $this->_api_url = "https://api.sandbox.namecheap.com/xml.response";
        } else {
            $this->_sandbox = false;
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
    
    
    public function sslList($search = '')
    {
        $search_data = [];
        $search_data['Command'] = "namecheap.ssl.getList";
        $search_data['PageSize'] = 100; // TODO: pagination
        if ($search)
        {
            $search_data['SearchTerm'] = $search;
        }

        /**
         * Add global fields (api_key, etc.) to the request array
         * @see https://www.namecheap.com/support/api/methods/ssl/create.aspx
         */
        $request = self::_add_global_fields($search_data);

        /**
         * make namecheap.ssl.getList API call and get the list back
         */
        $return = [];
        $response = $this->doRequest($request);
        foreach ($response->CommandResponse->SSLListResult->SSL as $certificate)
        {
            $return[(string)$certificate['CertificateID']] = [
                'id' => (string)$certificate['CertificateID'],
                'domain' => (string)$certificate['HostName'],
                'status' => self::_map_status((string)$certificate['Status']),
                'purchase_time' => strtotime((string)$certificate['PurchaseDate']),
                'expire_time' => strtotime((string)$certificate['ExpireDate']),
                'type' => self::_map_type_reverse((string)$certificate['SSLType']),
            ];
        }

        return $return;
    }


    /**
     * @param string $type single|wildcard
     * @return string $certificate_id
     *
     * @see https://www.namecheap.com/support/api/methods/ssl/create.aspx
     */
    public function sslCreate($type)
    {
        /* first step - "namecheap create" */
        $create_data = [];
        $create_data['Command'] = "namecheap.ssl.create";
        $create_data['Type'] = self::_map_type($type);
        $create_data['Years'] = 1;

        /**
         * Add global fields (api_key, etc.) to the request array
         * @see https://www.namecheap.com/support/api/methods/ssl/create.aspx
         */
        $request = self::_add_global_fields($create_data);

        /**
         * make namecheap.ssl.create API call and get a certificate ID back
         */
        $response = $this->doRequest($request);
        $certificate_id = (string)$response->CommandResponse->SSLCreateResult->SSLCertificate['CertificateID'];
        return $certificate_id;
    }


    /**
     * @see https://www.namecheap.com/support/api/methods/ssl/activate.aspx
     */
    public function sslActivate($certificate_id, $domain, $email, array $data, $dcv = 'http', $csr = '', $private_key = '', $webservertype = 'apacheopenssl', $approver_email = '')
    {
        return $this->_doActivate("activate",$certificate_id, $domain, $email, $data, $dcv, $csr, $private_key, $webservertype, $approver_email);
    }

    /**
     * @see https://www.namecheap.com/support/api/methods/ssl/reissue.aspx
     */
    public function sslReissue($certificate_id, $domain, $email, array $data, $dcv = 'http', $csr = '', $private_key = '', $webservertype = 'apacheopenssl', $approver_email = '')
    {
        return $this->_doActivate("reissue",$certificate_id, $domain, $email, $data, $dcv, $csr, $private_key, $webservertype, $approver_email);
    }

    /**
     * Since "activate" and "reissue" are basically the same action but with a different name a base method is used for
     * both calls and the only difference is the actual Namecheap command
     *
     * @param $action
     * @param $certificate_id
     * @param $domain
     * @param $email
     * @param array $data
     * @param string $dcv
     * @param string $csr
     * @param string $private_key
     * @param string $webservertype
     * @param string $approver_email
     * @return array
     * @throws \Exception
     */
    private function _doActivate($action, $certificate_id, $domain, $email, array $data, $dcv = 'http', $csr = '', $private_key = '', $webservertype = 'apacheopenssl', $approver_email = '')
    {
        /**
         * Create CSR / private key if not present
         */
        if (! $private_key)
        {
            $private_key = self::_create_key();
        }
        if (! $csr)
        {
            /**
             * check that $data contains the required fields
             */
            if (!self::_check_required_data($data)) {
                throw new \Exception('data does not contain all the required fields');
            }
            $csr = self::_create_csr($domain, $email, $data, $private_key);
        }

        /**
         * Fields to be added:
         * - Command
         * - CertificateID
         * - csr
         * - WebServerType
         * - ApproverEmail
         */
        if ($action == "reissue")
        {
            $activate_data['Command'] = 'namecheap.ssl.reissue';
        }
        else
        {
            $activate_data['Command'] = 'namecheap.ssl.activate';
        }

        $activate_data['CertificateID'] = $certificate_id;
        $activate_data['csr'] = $csr;
        $activate_data['AdminEmailAddress'] = $email;
        $activate_data['WebServerType'] = $webservertype;
        if ($dcv == "email")
        {
            $activate_data['ApproverEmail'] = $approver_email;
        }
        else
        {
            $activate_data['ApproverEmail'] = self::_map_dcv($dcv);
            if ($dcv == "dns")
            {
                $activate_data['DNSDCValidation'] = "true";
            }
            else
            {
                $activate_data['HTTPDCValidation'] = "true";
            }
        }

        /**
         * Add global fields (api_key, etc.) to the request array
         * @see https://www.namecheap.com/support/api/methods/ssl/create.aspx
         */
        $request = self::_add_global_fields($activate_data);

        /**
         * make namecheap.ssl.activate API call and get all the details back
         */
        $response = $this->doRequest($request);
        $is_success = (string)$response->CommandResponse->SSLActivateResult['IsSuccess'];
        if (strtolower($is_success) == "true")
        {
            $return = [];
            if ($dcv == "http")
            {
                // search for HttpDCValidation > DNS > FileName
                $filename = (string)$response->CommandResponse->SSLActivateResult->HttpDCValidation->DNS->FileName;
                $filecontent = (string)$response->CommandResponse->SSLActivateResult->HttpDCValidation->DNS->FileContent;
                if (strlen($filename) > 10 && strlen($filecontent) > 10)
                {
                    $return['domains'][$domain]['dcv'] = [
                        'type' => 'http',
                        'filename' => $filename,
                        'filecontent' => $filecontent,
                    ];
                }
                else
                {
                    throw new \Exception('HTTP validation - filename or filecontent not found');
                }
            }
            elseif ($dcv == "dns")
            {
                $hostname = (string)$response->CommandResponse->SSLActivateResult->DNSDCValidation->DNS->HostName;
                $target = (string)$response->CommandResponse->SSLActivateResult->DNSDCValidation->DNS->Target;
                if (strlen($hostname) > 10 && strlen($target) > 10)
                {
                    $return['domains'][$domain]['dcv'] = [
                        'type' => 'dns',
                        'hostname' => $hostname,
                        'target' => $target,
                    ];
                }
                else
                {
                    throw new \Exception('DNS validation - hostname or target not found');
                }

            }

            $return['id'] = $certificate_id;
            $return['status'] = self::SSL_STATUS_VALIDATING;
            $return['csr'] = $csr;
            $return['key'] = $private_key;

            /**
             * ok, we have everything, let's return the array
             */
            return $return;
        }

    }



    public function sslCheck($id, $domain = '', $dcv = 'http', $approver_email = '')
    {
        $return = [];

        $check_data = [];
        $check_data['Command'] = "namecheap.ssl.getinfo";
        $check_data['CertificateID'] = $id;
        $check_data['Returncertificate'] = "true";
        $check_data['Returntype'] = "individual";

        /**
         * Add global fields (api_key, etc.) to the request array
         */
        $request = self::_add_global_fields($check_data);

        /**
         * make API call and get a certificate info back
         */
        $response = $this->doRequest($request);
        $return['id'] = $id;
        $return['status'] = self::_map_status((string)$response->CommandResponse->SSLGetInfoResult['Status']);
        $return['expire_time'] = strtotime((string)$response->CommandResponse->SSLGetInfoResult['Expires']);
        $return['csr'] = (string)$response->CommandResponse->SSLGetInfoResult->CertificateDetails->CSR;
        $return['dcv'] = self::_map_dcv_reverse((string)$response->CommandResponse->SSLGetInfoResult->CertificateDetails->ApproverEmail);
        $return['domain'] = (string)$response->CommandResponse->SSLGetInfoResult->CertificateDetails->CommonName;
        foreach ($response->CommandResponse->SSLGetInfoResult->CertificateDetails->Certificates->Certificate as $certificate)
        {
            if (strtoupper((string)$certificate['type']) == "INTERMEDIATE")
            {
                $return['intermediate'] = $certificate;
            }
            else
            {
                $return['crt'] = $certificate;
            }
        }


        /**
         * if status is "validating" we also re-get the DCV data
         * @see https://www.namecheap.com/support/api/methods/ssl/editDCVMethod.aspx
         */
        if ($return['status'] == 'validating')
        {
            $dcv_data = [];
            $dcv_data['Command'] = "namecheap.ssl.editDCVMethod";
            $dcv_data['CertificateID'] = $id;
            if ($dcv == "email") {
                $dcv_data['DCVMethod'] = $approver_email;
            } else {
                $dcv_data['DCVMethod'] = self::_map_dcv($dcv ? $dcv : $return['dcv'], true);
            }

            /**
             * make API call and get a certificate info back
             */
            $request = self::_add_global_fields($dcv_data);
            $response = $this->doRequest($request);

            /**
             * save this data in $return
             */
            if ($dcv == "http")
            {
                // search for HttpDCValidation > DNS > FileName
                $filename = (string)$response->CommandResponse->SSLEditDCVMethodResult->HttpDCValidation->FileName;
                $filecontent = (string)$response->CommandResponse->SSLEditDCVMethodResult->HttpDCValidation->FileContent;
                if (strlen($filename) > 10 && strlen($filecontent) > 10)
                {
                    $return['domains'][$domain ? $domain : $return['domain']]['dcv'] = [
                        'type' => 'http',
                        'filename' => $filename,
                        'filecontent' => $filecontent,
                    ];
                }
                else
                {
                    throw new \Exception('HTTP validation - filename or filecontent not found');
                }
            }
            elseif ($dcv == "dns")
            {
                $hostname = (string)$response->CommandResponse->SSLEditDCVMethodResult->DNSDCValidation->HostName;
                $target = (string)$response->CommandResponse->SSLEditDCVMethodResult->DNSDCValidation->Target;
                if (strlen($hostname) > 10 && strlen($target) > 10)
                {
                    $return['domains'][$domain ? $domain : $return['domain']]['dcv'] = [
                        'type' => 'dns',
                        'hostname' => $hostname,
                        'target' => $target,
                    ];
                }
                else
                {
                    throw new \Exception('DNS validation - hostname or target not found');
                }

            }

        }

        return $return;
    }



    public function sslApproverEmails($domain)
    {
        // TODO: Implement sslApproverEmails() method.
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




    public function doRequest($data, $method = "GET")
    {
        $response_xml = $this->getHttpClient()->createRequest()
            ->setMethod($method)
            ->setUrl($this->_api_url)
            ->setData($data)
            ->send();
        $response_xml_body = $response_xml->content;
        if (strlen(trim($response_xml_body)))
        {
            $response = new \SimpleXMLElement($response_xml_body, LIBXML_NOCDATA);
            if ($response->Errors->Error)
            {
                throw new \Exception($response->Errors->Error, $response->Errors->Error['number']);
            }
            return $response;
        }
        else
        {
            throw new \Exception("Empty response");
        }
    }



    private function _add_global_fields(array $fields)
    {
        $common_fields = [
            'ApiUser' => $this->_api_user,
            'ApiKey' => $this->_api_key,
            'UserName' => $this->_api_user,
            'ClientIp' => self::_clientIP($this->_sandbox),
        ];

        $fields = array_merge($fields, $common_fields);
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

        $_type = $_mapping[strtolower($type)];
        if (! $_type) {
            throw new \InvalidArgumentException('wrong ssl certificate type: "' . $type . '"');
        }
        return $_type;
    }

    private static function _map_type_reverse($type)
    {
        $_mapping = [
            'positivessl' => 'single',
            'positivessl wildcard' => 'wildcard',
            'positivessl multi domain' => 'multidomain',
        ];

        $_type = $_mapping[strtolower($type)];
        if (! $_type) {
            $_type = $type;
        }
        return $_type;
    }


    /**
     * @param $dcv
     * @param bool $editDCVMethod   This is used when we need to data for editDCVMethod
     * @see https://www.namecheap.com/support/api/methods/ssl/editDCVMethod.aspx
     *
     * @return mixed
     */
    private static function _map_dcv($dcv, $editDCVMethod = false)
    {
        if ($editDCVMethod)
        {
            $_mapping = [
                'http' => 'HTTP_CSR_HASH',
                'dns' => 'CNAME_CSR_HASH',
            ];
        }
        else
        {
            $_mapping = [
                'http' => 'HTTPCSRHASH',
                'dns' => 'CNAMECSRHASH',
            ];
        }

        $_dcv = $_mapping[$dcv];
        if (! $_dcv) {
            throw new \InvalidArgumentException('wrong ssl certificate DCV: "' . $dcv . '"');
        }
        return $_dcv;
    }


    private static function _map_dcv_reverse($reverse_dcv, $editDCVMethod = false)
    {
        if ($editDCVMethod)
        {
            $_mapping = [
                'HTTP_CSR_HASH' => 'http',
                'CNAME_CSR_HASH' => 'dns',
            ];
        }
        else
        {
            $_mapping = [
                'HTTPCSRHASH' => 'http',
                'CNAMECSRHASH' => 'dns',
            ];
        }

        $_dcv = $_mapping[$reverse_dcv];
        if (! $_dcv) {
            throw new \InvalidArgumentException('wrong ssl certificate DCV: "' . $reverse_dcv . '"');
        }
        return $_dcv;
    }



    private static function _map_status($status)
    {
        $_mapping = [
            'active' => 'active',
            'purchased' => 'validating',
            'newpurchase' => 'pending',
            'purchaseerror' => 'error',
        ];
        $_default_status = "error";

        $_status = $_mapping[strtolower($status)];
        if (! $_status) {
            $_status = $_default_status;
        }
        return $_status;
    }

}