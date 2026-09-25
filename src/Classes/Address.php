<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Classes;

use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\AddressRow;

class Address extends PlcBase
{
    public function id(string $id): self
    {
        $this->add('ThirdPartyID', $id);

        return $this;
    }

    public function name(string $one, ?string $two = null, ?string $three = null, ?string $four = null): self
    {
        return $this->names(array_filter([$one, $two, $three, $four], static fn (?string $name): bool => $name !== null));
    }

    /** @param list<string> $names */
    public function names(array $names): self
    {
        unset($this->row['Name1'], $this->row['Name2'], $this->row['Name3'], $this->row['Name4']);

        foreach (array_slice(array_values($names), 0, 4) as $index => $name) {
            $this->add('Name'.($index + 1), $name);
        }

        return $this;
    }

    public function street(string $street): self
    {
        $street = trim($street);
        unset($this->row['HouseNumber']);

        if (preg_match('/^(.+?)\s+(\d+[[:alnum:]\/\.\-]*)$/u', $street, $matches) === 1) {
            return $this->route(trim($matches[1]))->streetNumber(trim($matches[2]));
        }

        return $this->route($street);
    }

    public function route(string $route): self
    {
        $this->add('AddressLine1', trim($route));

        return $this;
    }

    public function streetNumber(string $streetNumber): self
    {
        $this->add('HouseNumber', trim($streetNumber));

        return $this;
    }

    /** @deprecated Use streetNumber(). */
    public function street_number(string $street_number): self
    {
        return $this->streetNumber($street_number);
    }

    public function extra(string $extra): self
    {
        $this->add('AddressLine2', $extra);

        return $this;
    }

    public function postCode(string $postCode): self
    {
        $this->add('PostalCode', trim($postCode));

        return $this;
    }

    /** @deprecated Use postCode(). */
    public function post_code(string $post_code): self
    {
        return $this->postCode($post_code);
    }

    public function city(string $city): self
    {
        $this->add('City', trim($city));

        return $this;
    }

    public function countryCode(string $countryCode): self
    {
        $this->add('CountryID', strtoupper(trim($countryCode)));

        return $this;
    }

    /** @deprecated Use countryCode(). */
    public function country_code(string $country_code): self
    {
        return $this->countryCode($country_code);
    }

    public function phone(string $phone): self
    {
        $this->add('Tel1', $phone);

        return $this;
    }

    public function phoneAlt(string $phone): self
    {
        $this->add('Tel2', $phone);

        return $this;
    }

    /** @deprecated Use phoneAlt(). */
    public function phone_alt(string $phone_alt): self
    {
        return $this->phoneAlt($phone_alt);
    }

    public function fax(string $fax): self
    {
        $this->add('Fax', $fax);

        return $this;
    }

    public function email(string $email): self
    {
        $this->add('Email', trim($email));

        return $this;
    }

    public function website(string $website): self
    {
        $this->add('Homepage', trim($website));

        return $this;
    }

    public function vatId(string $vatId): self
    {
        $this->add('VatId', trim($vatId));

        return $this;
    }

    /** @deprecated Use vatId(). */
    public function vat_id(string $vat_id): self
    {
        return $this->vatId($vat_id);
    }

    public function eori(string $eori): self
    {
        $this->add('EORINumber', trim($eori));

        return $this;
    }

    public function taxNumber(string $taxNumber): self
    {
        $this->add('PersonalTaxNumber', trim($taxNumber));

        return $this;
    }

    /** @deprecated Use taxNumber(). */
    public function tax_number(string $tax_number): self
    {
        return $this->taxNumber($tax_number);
    }

    public function get(): AddressRow
    {
        return AddressRow::from($this->row);
    }
}
