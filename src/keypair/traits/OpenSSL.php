<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/6/18
 * Time: 9:48 PM
 */

namespace flipbox\keychain\keypair\traits;


/**
 * Trait OpenSSL
 * @package flipbox\keychain\keypair\traits
 */
trait OpenSSL
{
    public $startNoticeText = <<<EOF
You are about to be asked to enter information that will be incorporated
into your certificate request.
What you are about to enter is what is called a Distinguished Name or a DN.
There are quite a few fields but you can leave some blank
For some fields there will be a default value,
If you enter ' . ', the field will be left blank.
EOF;

    /**
     * @var string
     *  Country Name (OpenSSL Configuration)
     */
    public $countryName = 'US';
    /**
     * @var string
     *  State or Province Name (OpenSSL Configuration)
     */
    public $stateOrProvinceName = 'Colorado';
    /**
     * @var string
     *  Locality Name (OpenSSL Configuration)
     */
    public $localityName = 'Denver';
    /**
     * @var string
     *  Organization Name (OpenSSL Configuration)
     */
    public $organizationName = 'Example';
    /**
     * @var string
     *  Organization Unit Name (OpenSSL Configuration)
     */
    public $organizationalUnitName = 'IT';
    /**
     * @var string
     *  Common Name (OpenSSL Configuration)
     */
    public $commonName = 'example.com';
    /**
     * @var string
     *  Email (OpenSSL Configuration)
     */
    public $emailAddress = 'it@example.com';

    /**
     * @var string
     */
    public $description = 'Tell me what this is for.';

    public $labels = [
        'countryName'            => 'Country Name (2 letter code)',
        'stateOrProvinceName'    => 'State or Province Name (full name)',
        'localityName'           => 'Locality Name (eg, city)',
        'organizationName'       => 'Organization Name (eg, company)',
        'organizationalUnitName' => 'Organizational Unit Name (eg, section)',
        'commonName'             => 'Common Name (eg, fully qualified host name)',
        'emailAddress'           => 'Email Address',
        'description'            => 'Description',
    ];

    public function getAttributes()
    {
        return [
            'countryName'            => [
                'default' => $this->countryName,
            ],
            'stateOrProvinceName'    => [
                'default' => $this->stateOrProvinceName,
            ],
            'localityName'           => [
                'default' => $this->localityName,
            ],
            'organizationName'       => [
                'default' => $this->organizationName,
            ],
            'organizationalUnitName' => [
                'default' => $this->organizationalUnitName,
            ],
            'commonName'             => [
                'default' => $this->commonName,
            ],
            'emailAddress'           => [
                'default' => $this->emailAddress,
            ],
            'description'            => [
                'default' => $this->description,
            ],
        ];

    }

}