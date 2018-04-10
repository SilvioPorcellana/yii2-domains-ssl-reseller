<?php
/**
 * @link https://github.com/SilvioPorcellana/yii2-domains-ssl-reseller
 * @copyright Copyright (c) 2018 The Maven System
 * @license https://opensource.org/licenses/BSD-3-Clause
 * @see https://www.namecheap.com/support/api/methods/
 */

namespace TheMavenSystem\DomainsSSLReseller\Reseller;

use TheMavenSystem\DomainsSSLReseller\AbstractReseller;
use TheMavenSystem\DomainsSSLReseller\Http\RequestManager;
use TheMavenSystem\DomainsSSLReseller\ResellerInterface;

class Namecheap extends AbstractReseller
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

    /**
     * This is the method for the Certificate creation part. This part is divided in 2:
     * - namecheap.ssl.create       This is for the "start" of the creation process, and a CertificateID is returned
     * - namecheap.ssl.activate     This is for the actual start of the activation process, where the CSR,
     *
     * @param string $type
     * @param string $domains
     * @param string $email
     * @param array $data
     * @param string $dcv
     * @param string $csr
     * @param string $key
     * @param string $webservertype
     */
    public function sslCreate($type, $domain, $email, array $data, $dcv = 'http', $csr = '', $private_key = '', $webservertype = 'apacheopenssl', $approver_email = '')
    {
        /**
         * check that $data contains the required fields
         */
        if (! self::_check_required_data($data))
        {
            throw new \BadMethodCallException('data does not contain all the required fields');
        }

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
        $response_xml = $this->getHttpClient()->createRequest()
            ->setMethod('GET')
            ->setUrl($this->_api_url)
            ->setData($request)
            ->send();
        $response_xml_body = $response_xml->content;
        if (strlen(trim($response_xml_body)))
        {
            $response = new \SimpleXMLElement($response_xml_body);
            $certificate_id = $response->CommandResponse->SSLCreateResult->SSLCertificate['CertificateID'];
        }
        else
        {
            throw new \BadMethodCallException();
        }


        /**
         * If we correctly have a certificate ID here we can proceed with the namecheap.ssl.activate call
         * @see https://www.namecheap.com/support/api/methods/ssl/activate.aspx
         */
        if ($certificate_id)
        {
            $activate_data = [];

            /**
             * Create CSR / private key if not present
             */
            if (! $private_key)
            {
                $private_key = self::_create_key();
            }
            if (! $csr)
            {
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
            $activate_data['Command'] = 'namecheap.ssl.activate';

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
            }

            /**
             * Add global fields (api_key, etc.) to the request array
             * @see https://www.namecheap.com/support/api/methods/ssl/create.aspx
             */
            $request = self::_add_global_fields($activate_data);

            /**
             * make namecheap.ssl.activate API call and get all the details back
             */
            $response_xml = $this->getHttpClient()->createRequest()
                ->setMethod('GET')
                ->setUrl($this->_api_url)
                ->setData($request)
                ->send();
            $response_xml_body = $response_xml->content;
            if (strlen(trim($response_xml_body)))
            {
                /**
                 * This is the array that we will return with all the details about this certificate
                 * @see ResellerInterface;
                 */
                $return = [];

                $response = new \SimpleXMLElement($response_xml_body);
                $is_success = $response->CommandResponse->SSLActivateResult['IsSuccess'];
                if (strtolower($is_success) == "true")
                {
                    if ($dcv == "http")
                    {
                        // search for HttpDCValidation > DNS > FileName
                        $filename = $response->CommandResponse->SSLActivateResult->HttpDCValidation->DNS->FileName;
                        $filecontent = $response->CommandResponse->SSLActivateResult->HttpDCValidation->DNS->FileContent;
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
                        $hostname = $response->CommandResponse->SSLActivateResult->DNSDCValidation->DNS->HostName;
                        $target = $response->CommandResponse->SSLActivateResult->DNSDCValidation->DNS->Target;
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
                else
                {
                    throw new \Exception('activate failure');
                }
            }
            else
            {
                throw new \BadMethodCallException();
            }

        }

    }


    public function sslCheck($id)
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
        $response_xml = $this->getHttpClient()->createRequest()
            ->setMethod('GET')
            ->setUrl($this->_api_url)
            ->setData($request)
            ->send();
        $response_xml_body = $response_xml->content;
        if (strlen(trim($response_xml_body)))
        {
            $response = new \SimpleXMLElement($response_xml_body);
            $return['status'] = self::_map_status($response->CommandResponse->SSLGetInfoResult['Status']);
            $return['expires_on'] = strtotime($response->CommandResponse->SSLGetInfoResult['Expires']);
            $return['csr'] = $response->CommandResponse->CertificateDetails->CSR;
            foreach ($response->CommandResponse->CertificateDetails->Certificates->Certificate as $certificate)
            {
                if (strtoupper($certificate['type']) == "INTERMEDIATE")
                {
                    $return['intermediate'] = $certificate;
                }
                else
                {
                    $return['crt'] = $certificate;
                }
            }
        }
        else
        {
            throw new \BadMethodCallException();
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


    private static function _map_dcv($dcv)
    {
        $_mapping = [
            'http' => 'HTTPCSRHASH',
            'dns' => 'CNAMECSRHASH',
        ];

        $_dcv = $_mapping[$dcv];
        if (! $_dcv) {
            throw new \InvalidArgumentException('wrong ssl certificate DCV: "' . $dcv . '"');
        }
        return $_dcv;
    }

    
    private static function _map_status($status)
    {
        $_mapping = [
            'Active' => 'active',
            'Purchased' => 'validating',
            'Newpurchase' => 'pending',
            'Purchaseerror' => 'error',
        ];
        $_default_status = "error";

        $_status = $_mapping[$status];
        if (! $_status) {
            $_status = $_default_status;
        }
        return $_status;
    }

}