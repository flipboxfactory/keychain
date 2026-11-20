<?php

declare(strict_types=1);

namespace flipbox\keychain\keypair\traits;

/**
 * Labels and suggested values for the OpenSSL key pair form.
 */
trait OpenSSL
{
    public string $countryName = 'US';

    public string $stateOrProvinceName = 'Colorado';

    public string $localityName = 'Denver';

    public string $organizationName = 'Example';

    public string $organizationalUnitName = 'IT';

    public string $commonName = 'example.com';

    public string $emailAddress = 'it@example.com';

    public string $description = 'Tell me what this is for.';

    /**
     * @var array<string, string>
     */
    public array $labels = [
        'countryName' => 'Country Name (2 letter code)',
        'stateOrProvinceName' => 'State or Province Name (full name)',
        'localityName' => 'Locality Name (eg, city)',
        'organizationName' => 'Organization Name (eg, company)',
        'organizationalUnitName' => 'Organizational Unit Name (eg, section)',
        'commonName' => 'Common Name (eg, fully qualified host name)',
        'emailAddress' => 'Email Address',
        'description' => 'Description',
    ];

    /**
     * The form fields, with their suggested values.
     *
     * @return array<string, array{default: string}>
     */
    public function getAttributes(): array
    {
        $attributes = [];
        foreach (array_keys($this->labels) as $attribute) {
            $attributes[$attribute] = ['default' => $this->$attribute];
        }

        return $attributes;
    }
}
