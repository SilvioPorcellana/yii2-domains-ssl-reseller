<p align="center">
    <a href="https://www.themavensystem.com/" target="_blank">
        <img src="https://core3.imgix.net/58fe072691aadlogo1bluetransparent.png" height="75">
    </a>
</p>
<h1 align="center">
Domains/SSL Reseller API - Yii2 Extension
</h1>

This is an Yii2 extension that provides a single interface to domains and SSL resellers APIs such as Namecheap and Godaddy.

The goal of this extension is to allow anybody with a Yii2-based system to use the domains and SSL purchase/management features of various providers using a common interface and some "standard" and very simple methods, abstracting  

**THIS IS AN ALPHA VERSION! Most methods are not implemented yet, tests are not complete and so you SHOULD NOT use this extension in production** 

# Requirements
Yii2

[Yii2 HTTP Client](https://github.com/yiisoft/yii2-httpclient)

# Installation

Since this extension is not distributed on repositories yet, the best way to use it is by requiring it in your Yii2 `composer.json` main file asking Composer to get it from this GitHub repository, like this:
```
"require": {
        ...
        "themavensystem/yii2-domains-ssl-reseller": "*"
    },
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/SilvioPorcellana/yii2-domains-ssl-reseller"
        }
    ],
```
# Usage

Once installed, this extension can be added and used like any other Yii2 extension - by simply `use`ing it in your controllers, models etc.:
```$xslt
use TheMavenSystem\DomainsSSLReseller
```
From this moment you can use the `Factory` method to instantiate a reseller instance and use the available methods, like this:
```$xslt
$reseller = DomainsSSLReseller\Factory::create("namecheap", "API_USER", "API_KEY", $sandbox);
// ...
$result = $reseller->sslList();
// ...
$certificate_data = $reseller->sslCheck($certificate_id);
// ...
$data = [
    'organization_name' => "Acme Inc.",
    'organization_unit_name' => "Explosives & Stuff",
    'country' => "US",
    'state' => "California",
    'city' => "Los Angeles",
    'address' => "Los Angeles",
    'zip' => "00189",
];
$certificate_data = $reseller->sslActivate($certificate_id, $domain, $owner_email, $data);
```