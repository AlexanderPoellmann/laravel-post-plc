<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\DataTransferObjects;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

class AddressRow extends Data
{
    public function __construct(
        public readonly ?string $ThirdPartyID,
        #[MapName('VATID')]
        public readonly ?string $VatId,
        public readonly string $Name1,
        public readonly ?string $Name2,
        public readonly ?string $Name3,
        public readonly ?string $Name4,
        public readonly string $AddressLine1,
        public readonly ?string $HouseNumber,
        public readonly ?string $AddressLine2,
        public readonly string $PostalCode,
        public readonly string $CountryID,
        public readonly string $City,
        public readonly ?string $Tel1,
        public readonly ?string $Tel2,
        public readonly ?string $Fax,
        public readonly ?string $Email,
        public readonly ?string $Homepage,
        public readonly ?string $EORINumber,
        public readonly ?string $PersonalTaxNumber,
        public readonly ?string $AuthorizedExporterIdentificationNumber = null,
        public readonly ?string $CustomsDutyAccountNumber = null,
        public readonly ?string $CustomsTaxAccountNumber = null,
        public readonly ?string $ProvinceCode = null,
    ) {}

    public function hasPhone(): bool
    {
        return filled($this->Tel1) || filled($this->Tel2);
    }

    public function hasEmail(): bool
    {
        return filled($this->Email);
    }

    public function hasPhoneOrEmail(): bool
    {
        return $this->hasPhone() || $this->hasEmail();
    }

    public function countryCode(): string
    {
        return strtoupper($this->CountryID);
    }
}
