<?php
/**
 * @link https://github.com/SilvioPorcellana/yii2-domains-ssl-reseller
 * @copyright Copyright (c) 2018 Mobisit Inc.
 * @license https://opensource.org/licenses/BSD-3-Clause
 */

namespace TheMavenSystem\DomainsSSLReseller;

interface ResellerInterface
{

    /**
     * Checks if a domain is available
     *
     * ```php
     * $is_available = $reseller->domainCheck($domains);
     * ```
     *
     * @param mixed $domains A single string or an array of strings with the domain names (domain.tld) to be checked
     *
     * @return bool
     */
    public function domainCheck($domains);

    /**
     * Registers a domain
     *
     * ```php
     * $reseller->domainRegister($domain, $years, $data);
     * ```
     *
     * @param string $domain The full domain, such as "mydomain.com" (needs to be available)
     * @param integer $years The number of years for which you want to register this domain
     * @param array $data An associative array with the following structure (the initial, "base" fields are all required):
     *      [
     *          'first_name' => "Silvio",
     *          'last_name' => "Porcellana",
     *          'address' => "Via del Macao 9",
     *          'city' => "Rome",
     *          'state' => "Lazio",
     *          'country' => "Italy",
     *          'phone' => "+39.328865456",
     *          'zip' => "00189",
     *          'email' => "silvio.porcellana@gmail.com",
     *          'tech_first_name' => "Silvio" (if empty will use main data),
     *          'tech_last_name' => "Porcellana" (if empty will use main data),
     *          'tech_address' => "Via del Macao 9" (if empty will use main data),
     *          'tech_city' => "Rome" (if empty will use main data),
     *          'tech_state' => "Lazio" (if empty will use main data),
     *          'tech_country' => "Italy" (if empty will use main data),
     *          'tech_phone' => "+39.328865456" (if empty will use main data),
     *          'tech_zip' => "00189",
     *          'tech_email' => "silvio.porcellana@gmail.com" (if empty will use main data),
     *          'admin_first_name' => "Silvio" (if empty will use main data),
     *          'admin_last_name' => "Porcellana" (if empty will use main data),
     *          'admin_address' => "Via del Macao 9" (if empty will use main data),
     *          'admin_city' => "Rome" (if empty will use main data),
     *          'admin_state' => "Lazio" (if empty will use main data),
     *          'admin_country' => "Italy" (if empty will use main data),
     *          'admin_phone' => "+39.328865456" (if empty will use main data),
     *          'admin_zip' => "00189",
     *          'admin_email' => "silvio.porcellana@gmail.com" (if empty will use main data),
     *          'auxbilling_first_name' => "Silvio" (if empty will use main data),
     *          'auxbilling_last_name' => "Porcellana" (if empty will use main data),
     *          'auxbilling_address' => "Via del Macao 9" (if empty will use main data),
     *          'auxbilling_city' => "Rome" (if empty will use main data),
     *          'auxbilling_state' => "Lazio" (if empty will use main data),
     *          'auxbilling_country' => "Italy" (if empty will use main data),
     *          'auxbilling_phone' => "+39.328865456" (if empty will use main data),
     *          'auxbilling_zip' => "00189",
     *          'auxbilling_email' => "silvio.porcellana@gmail.com" (if empty will use main data),
     *      ]
     *
     * @return void
     */
    /* TODO */
    public function domainRegister($domain, $data);

    /* TODO */
    public function domainRenew($domain);

    /* TODO */
    public function domainInfo($domain);


    /**
     * This function creates a new certificate, bundling all the required steps
     * (for example on Namecheap there's the "create" call and then the "activate" call) and gets it in the "validating" state
     * After this usually a
     *
     * ```php
     * $certificate_request = $reseller->sslCreate($domain, $years, $data);
     * // in $certificate_request we now have the certificate ID, the CSR and the Key
     * // we need to store these so we can use them later for Apache, etc.
     * ```
     *
     *
     * @param string $type Can be one of the following: "single", "wildcard", "multidomain"
     * @param mixed $domains The domain(s) (as a string or as an array) for which we are creating this certificate. Max 3 domains.
     * @param string $email The email address to send signed SSL certificate files to
     * @param array $data An associative array with the following details:
     *      [
     *          'organization_name' => "Acme Inc.",
     *          'organization_unit_name' => "Explosives & Stuff"
     *          'country' => "US",
     *          'state' => "California",
     *          'city' => "Los Angeles",
     *          'address' => "Los Angeles",
     *          'zip' => "00189",
     *      ]
     * @param string $dcv The validation type (default is "http" whit a file placed in the /.well-known/pki-validation/ directory
     * @param string $csr If absent a new CSR will be created with the $data provided
     * @param string $key If absent a new key will be created
     * @param string $webservertype Allowed values are listed in https://www.namecheap.com/support/api/methods/ssl/activate.aspx
     *
     * @return array $certificate Data structure with the following fields:
     *      [
     *          'id' => "123456" (the ID of the certificate)
     *          'status' => "validating" (the current status of the certificate, chosing among: "validating" or "error")
     *          'csr' => "-----BEGIN CERTIFICATE----- MIIFBDCCA+ygAwIBAgIQJXnrY7043Qfa... -----END CERTIFICATE-----",
     *          'key' => "-----BEGIN CERTIFICATE----- AgIBATANBgkqhkiG9w0BAQUFADBvMQswCQYD... -----END CERTIFICATE-----",
     *          'domains' => [
     *              'secure.example.com' => [
     *                  'dcv' => [
     *                      'type' => "http",
     *                      'filename' => '4E3324A380B58813D5A2F32AA13A96F0.txt',
     *                      'filecontent' => '6694010FAC8ED8F806F1EAD56A1A0478DE6620A256BB8C356A8DD2146B00E884 comodoca.com 5a955211b1f8c',
     *                  ],
     *              'login.example.com' => [
     *                  'dcv' => [
     *                      'type' => "cname",
     *                      'hostname' => '_4E3324A380B58813D5A2F32AA13A96F0.login.example.com',
     *                      'target' => '6694010FAC8ED8F806F1EAD56A1A0478.DE6620A256BB8C356A8DD2146B00E884.5a955211b1f8c.comodoca.com',
     *                  ],
     *              ],
     *              ...
     *          ]
     *      ]
     */
    public function sslCreate($type, $domains, $email, array $data, $dcv = 'http', $csr = '', $key = '', $webservertype = 'apacheopenssl');

    /**
     * ```php
     * $ssl_check = $reseller->sslCheck($id);
     * if ($ssl_check['status'] == "active") {
     *      // certificate is active, we can save the certificate contents
     * } else {
     *      // not active yet, still validating with the DCV we chose initially
     * }
     * ```
     *
     * @param string $id
     *
     * @return array $certificate Data structure with the following fields:
     *      [
     *          'id' => "123456" (the ID of the certificate)
     *          'status' => "active" (the current status of the certificate, chosing among: "active", "validating" or "error")
     *          'csr' => "-----BEGIN CERTIFICATE----- MIIFBDCCA+ygAwIBAgIQJXnrY7043Qfa... -----END CERTIFICATE-----",
     *          'crt' => "-----BEGIN CERTIFICATE----- AgIBATANBgkqhkiG9w0BAQUFADBvMQswCQYD... -----END CERTIFICATE-----",
     *          'intermediate' => "-----BEGIN CERTIFICATE----- AgIBATANBgkqhkiG9w0BAQUFADBvMQswCQYD... -----END CERTIFICATE-----",
     *      ]
     */
    public function sslCheck($id);


    // user
    public function userDomainsList();
    public function userSslList();
    public function userBalance();
}