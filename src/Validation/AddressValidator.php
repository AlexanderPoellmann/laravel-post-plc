<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Validation;

use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\AddressRow;

final class AddressValidator
{
    public function validate(AddressRow $address, string $path = 'Address'): ValidationResult
    {
        $result = new ValidationResult;

        $required = [
            'Name1' => $address->Name1,
            'AddressLine1' => $address->AddressLine1,
            'PostalCode' => $address->PostalCode,
            'CountryID' => $address->CountryID,
            'City' => $address->City,
        ];

        foreach ($required as $field => $value) {
            if (trim($value) === '') {
                $result->add('address.required', $path.'.'.$field, $field.' is required.');
            }
        }

        if (! preg_match('/^[A-Z]{2}$/', $address->countryCode())) {
            $result->add('address.country', $path.'.CountryID', 'CountryID must be a two-letter ISO country code.');
        }

        if ($address->Email !== null && $address->Email !== '' && filter_var($address->Email, FILTER_VALIDATE_EMAIL) === false) {
            $result->add('address.email', $path.'.Email', 'Email must contain a valid email address.');
        }

        $values = [
            'ThirdPartyID' => [$address->ThirdPartyID, 20],
            'VatId' => [$address->VatId, 20],
            'Name1' => [$address->Name1, 100],
            'Name2' => [$address->Name2, 100],
            'Name3' => [$address->Name3, 50],
            'Name4' => [$address->Name4, 50],
            'AddressLine1' => [$address->AddressLine1, 60],
            'HouseNumber' => [$address->HouseNumber, 20],
            'AddressLine2' => [$address->AddressLine2, 60],
            'PostalCode' => [$address->PostalCode, 15],
            'CountryID' => [$address->CountryID, 2],
            'City' => [$address->City, 100],
            'Tel1' => [$address->Tel1, 100],
            'Tel2' => [$address->Tel2, 100],
            'Fax' => [$address->Fax, 100],
            'Email' => [$address->Email, 100],
            'Homepage' => [$address->Homepage, 100],
            'EORINumber' => [$address->EORINumber, 17],
            'PersonalTaxNumber' => [$address->PersonalTaxNumber, 20],
        ];

        foreach ($values as $field => [$value, $limit]) {
            if ($value !== null && mb_strlen($value) > $limit) {
                $result->add('address.max_length', $path.'.'.$field, sprintf('%s may not exceed %d characters.', $field, $limit));
            }
        }

        return $result;
    }
}
