<?php
/**
 * @link https://github.com/SilvioPorcellana/yii2-domains-ssl-reseller
 * @copyright Copyright (c) 2018 Mobisit Inc.
 * @license https://opensource.org/licenses/BSD-3-Clause
 */

namespace TheMavenSystem\DomainsSSLReseller;
use TheMavenSystem\DomainsSSLReseller\Reseller\Namecheap;


/**
 * The `Factory` class exists as a convenient way to create the appropriate class for dealing with the different resellers APIs
 */
final class Factory
{
    /**
     * @param string $reseller_name The name of a reseller, for example "godaddy", "namecheap", ...
     * @param string $api_user Used for authenticating reseller API calls
     * @param string $api_key Used for authenticating reseller API calls
     *
     * Creates a new reseller instance
     *
     * ```php
     * $reseller = TheMavenSystem\DomainsSSLReseller\Factory::create($reseller_name, "username", "1234");
     * ```
     *
     * This method always returns an instance implementing `ResellerInterface`,
     * the actual reseller class that implements the methods for talking with the domain/ssl reseller
     *
     * This method should usually only be called once at the beginning of the program.
     *
     */
    public static function create($reseller_name, $api_user = '', $api_key = '')
    {
        switch ($reseller_name) {
            case "namecheap":
                return new Namecheap($api_user, $api_key);
                break;
            default:
                throw new \InvalidArgumentException("Invalid reseller name");
        }
    }

}