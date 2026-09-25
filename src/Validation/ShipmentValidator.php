<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Validation;

use AlexanderPoellmann\LaravelPostPlc\Contracts\CustomsRequirementResolver;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\ColloArticleRow;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\FeatureRow;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\ShipmentRow;
use AlexanderPoellmann\LaravelPostPlc\Enums\CustomsOptions;
use AlexanderPoellmann\LaravelPostPlc\Enums\Features;
use AlexanderPoellmann\LaravelPostPlc\Enums\PostProductCodes;
use AlexanderPoellmann\LaravelPostPlc\Enums\ReturnOptions;
use AlexanderPoellmann\LaravelPostPlc\Resolvers\AllowedServices;

final readonly class ShipmentValidator
{
    public function __construct(
        private CustomsRequirementResolver $customsRequirementResolver,
        private AddressValidator $addressValidator = new AddressValidator,
    ) {}

    public function validate(ShipmentRow $shipment, ?AllowedServices $allowedServices = null): ValidationResult
    {
        $result = new ValidationResult;

        $this->validateCredentials($shipment, $result);
        $result->merge($this->addressValidator->validate($shipment->OURecipientAddress, 'OURecipientAddress'));
        $this->validateShipmentFields($shipment, $result);

        if ($shipment->OUShipperAddress !== null) {
            $result->merge($this->addressValidator->validate($shipment->OUShipperAddress, 'OUShipperAddress'));
        }

        if ($shipment->AlternativeReturnOrgUnitAddress !== null) {
            $result->merge($this->addressValidator->validate($shipment->AlternativeReturnOrgUnitAddress, 'AlternativeReturnOrgUnitAddress'));
        }

        if ($shipment->OUImporterAddress !== null) {
            $result->merge($this->addressValidator->validate($shipment->OUImporterAddress, 'OUImporterAddress'));
        }

        $this->validateProduct($shipment, $allowedServices, $result);
        $this->validateFeatures($shipment, $allowedServices, $result);
        $this->validateReturnOptions($shipment, $result);
        $this->validateWeightRequirements($shipment, $result);
        $this->validateCustoms($shipment, $result);

        return $result;
    }

    private function validateCredentials(ShipmentRow $shipment, ValidationResult $result): void
    {
        foreach ([
            'ClientID' => $shipment->ClientID,
            'OrgUnitID' => $shipment->OrgUnitID,
            'OrgUnitGuid' => $shipment->OrgUnitGuid,
        ] as $path => $value) {
            if (trim($value) === '') {
                $result->add('configuration.missing', $path, sprintf('%s must be configured.', $path));
            }
        }
    }

    private function validateShipmentFields(ShipmentRow $shipment, ValidationResult $result): void
    {
        $values = [
            'CostCenterThirdPartyID' => [$shipment->CostCenterThirdPartyID, 50],
            'Number' => [$shipment->Number, 50],
            'OUShipperReference1' => [$shipment->OUShipperReference1, 100],
            'OUShipperReference2' => [$shipment->OUShipperReference2, 100],
            'DeliveryInstruction' => [$shipment->DeliveryInstruction, 512],
            'MovementReferenceNumber' => [$shipment->MovementReferenceNumber, 50],
            'CustomsDescription' => [$shipment->CustomsDescription, 512],
            'CustomerProduct' => [$shipment->CustomerProduct, 15],
            'RefBarcodeType' => [$shipment->RefBarcodeType, 1],
        ];

        foreach ($values as $field => [$value, $limit]) {
            if ($value !== null && mb_strlen($value) > $limit) {
                $result->add('shipment.max_length', $field, sprintf('%s may not exceed %d characters.', $field, $limit));
            }
        }

        if ($shipment->ShipmentDocumentEntryList !== null) {
            foreach ($shipment->ShipmentDocumentEntryList as $index => $document) {
                if ($document->Quantity <= 0) {
                    $result->add('shipment.document_quantity', sprintf('ShipmentDocumentEntryList.%s.Quantity', $index), 'Document quantity must be greater than zero.');
                }

                if ($document->Number !== null && mb_strlen($document->Number) > 50) {
                    $result->add('shipment.max_length', sprintf('ShipmentDocumentEntryList.%s.Number', $index), 'Document number may not exceed 50 characters.');
                }
            }
        }

        if ($shipment->ColloList !== null) {
            foreach ($shipment->ColloList as $colloIndex => $collo) {
                foreach (['Weight' => $collo->Weight, 'Length' => $collo->Length, 'Width' => $collo->Width, 'Height' => $collo->Height] as $field => $value) {
                    if ($value !== null && $value < 0) {
                        $result->add('collo.non_negative', sprintf('ColloList.%s.%s', $colloIndex, $field), $field.' may not be negative.');
                    }
                }

                if ($collo->ColloCodeList !== null) {
                    foreach ($collo->ColloCodeList as $codeIndex => $code) {
                        if (mb_strlen($code->Code) > 50) {
                            $result->add('collo.code_too_long', sprintf('ColloList.%s.ColloCodeList.%s.Code', $colloIndex, $codeIndex), 'Collo code may not exceed 50 characters.');
                        }
                    }
                }

                if ($collo->ColloArticleList !== null) {
                    foreach ($collo->ColloArticleList as $articleIndex => $article) {
                        $base = sprintf('ColloList.%s.ColloArticleList.%s', $colloIndex, $articleIndex);
                        if (mb_strlen($article->ArticleName) > 100) {
                            $result->add('article.max_length', $base.'.ArticleName', 'ArticleName may not exceed 100 characters.');
                        }
                        if ($article->ArticleNumber !== null && mb_strlen($article->ArticleNumber) > 20) {
                            $result->add('article.max_length', $base.'.ArticleNumber', 'ArticleNumber may not exceed 20 characters.');
                        }
                    }
                }
            }
        }
    }

    private function validateProduct(ShipmentRow $shipment, ?AllowedServices $allowedServices, ValidationResult $result): void
    {
        $productCode = $shipment->productCode();
        $knownProduct = $productCode->known();
        $destination = $shipment->OURecipientAddress->countryCode();
        $origin = $shipment->OUShipperAddress?->countryCode() ?? 'AT';

        if ($origin !== 'AT' && $destination !== 'AT') {
            $result->add('10044', 'OUShipperAddress', 'At least the sender or recipient must be located in Austria.');
        }

        if ($knownProduct !== null && ! $knownProduct->isAvailableForRoute($origin, $destination)) {
            $result->add(
                '10056',
                'DeliveryServiceThirdPartyID',
                sprintf('Product %s is not valid for route %s to %s.', $productCode->value, $origin, $destination),
            );
        }

        if ($allowedServices !== null && ! $allowedServices->allowsProduct($productCode)) {
            $result->add(
                '10055',
                'DeliveryServiceThirdPartyID',
                sprintf('Product %s is not returned by GetAllowedServicesForCountry for this destination.', $productCode->value),
            );
        }
    }

    private function validateFeatures(ShipmentRow $shipment, ?AllowedServices $allowedServices, ValidationResult $result): void
    {
        if ($shipment->knownProduct() === PostProductCodes::NextDay
            && (! $shipment->OURecipientAddress->hasPhone() || ! $shipment->OURecipientAddress->hasEmail())) {
            $result->add('10070', 'OURecipientAddress', 'Next Day requires both a recipient phone number and email address.');
        }

        if ($shipment->FeatureList === null) {
            return;
        }

        $features = [];

        foreach ($shipment->FeatureList as $index => $feature) {
            if (! $feature instanceof FeatureRow) {
                continue;
            }

            $featureCode = $feature->code();
            $code = $featureCode->value;
            $path = sprintf('FeatureList.%s', $index);

            if (isset($features[$code])) {
                $result->add('feature.duplicate', $path.'.ThirdPartyID', sprintf('Feature %s may only be supplied once.', $code));
            }

            $features[$code] = $feature;
            $this->validateFeatureValues($feature, $shipment, $path, $result);

            if ($allowedServices !== null
                && $allowedServices->allowsProduct($shipment->productCode())
                && ! $allowedServices->allowsFeature($shipment->productCode(), $featureCode)) {
                $result->add(
                    '10081',
                    $path.'.ThirdPartyID',
                    sprintf('Feature %s is not allowed for product %s.', $code, $shipment->productCode()->value),
                );
            }
        }

        if (isset($features[Features::PreferredPickupStation->value], $features[Features::PersonalDelivery->value])) {
            $result->add('10051', 'FeatureList', 'Personal delivery cannot be combined with delivery to a pickup station.');
        }

        if (isset($features[Features::PreferredPickupStation->value])
            && (isset($features[Features::CashOnDelivery->value]) || isset($features[Features::CashOnDeliveryInternational->value]))) {
            $result->add('10052', 'FeatureList', 'Cash on delivery cannot be combined with delivery to a pickup station.');
        }

    }

    private function validateFeatureValues(
        FeatureRow $feature,
        ShipmentRow $shipment,
        string $path,
        ValidationResult $result,
    ): void {
        foreach ([
            'Value1' => $feature->Value1,
            'Value2' => $feature->Value2,
            'Value3' => $feature->Value3,
            'Value4' => $feature->Value4,
        ] as $property => $value) {
            if ($value !== null && mb_strlen($value) > 50) {
                $result->add('feature.value_too_long', $path.'.'.$property, $property.' may not exceed 50 characters.');
            }
        }

        switch ($feature->code()->known()) {
            case Features::CashOnDelivery:
            case Features::CashOnDeliveryInternational:
                $this->requirePositiveAmount($feature->Value1, $path.'.Value1', $result);
                $this->requireCurrency($feature->Value2, $path.'.Value2', $result);
                $this->requirePipeParts($feature->Value3, 3, $path.'.Value3', $result);
                $this->requireValue($feature->Value4, $path.'.Value4', $result);
                break;

            case Features::ValueShipment:
                $this->requirePositiveAmount($feature->Value1, $path.'.Value1', $result);
                $this->requireCurrency($feature->Value2, $path.'.Value2', $result);
                break;

            case Features::AdditionalInsurance:
                $this->requirePositiveAmount($feature->Value1, $path.'.Value1', $result);
                if ($feature->Value2 !== null) {
                    $this->requireCurrency($feature->Value2, $path.'.Value2', $result);
                }
                break;

            case Features::PreferredPickupBranch:
            case Features::PreferredPickupStation:
            case Features::PosteRestante:
                if ($feature->code()->known() === Features::PosteRestante && $shipment->OURecipientAddress->countryCode() === 'DE') {
                    if ($this->hasValue($feature->Value1)) {
                        $result->add('feature.germany_branch_key', $path.'.Value1', 'For German poste-restante delivery only the feature ID must be sent; omit the branch key.');
                    }
                } else {
                    $this->requireNumericValue($feature->Value1, $path.'.Value1', $result);
                }

                if (in_array($feature->code()->known(), [Features::PreferredPickupBranch, Features::PreferredPickupStation], true)
                    && ! $shipment->OURecipientAddress->hasPhoneOrEmail()) {
                    $result->add(
                        $feature->code()->known() === Features::PreferredPickupBranch ? '10030' : '10032',
                        'OURecipientAddress',
                        'Preferred branch/station delivery requires a recipient phone number or email address.',
                    );
                }
                break;

            case Features::POBox:
                $this->requireNumericValue($feature->Value1, $path.'.Value1', $result);
                $this->requireValue($feature->Value2, $path.'.Value2', $result);
                break;

            case Features::SenderNotification:
            case Features::PreferredDropLocation:
                $this->requireValue($feature->Value1, $path.'.Value1', $result);
                break;

            case Features::ShortStoragePeriod:
            case Features::PreferredTimeWindow:
                $this->requireValue($feature->Value1, $path.'.Value1', $result);
                break;

            case Features::LimitedQuantityDangerousGoods:
            case Features::PreferredDate:
                $this->requireNumericValue($feature->Value1, $path.'.Value1', $result);
                break;

            case Features::PreferredNeighbor:
                $this->requireValue($feature->Value1, $path.'.Value1', $result);
                $this->requireValue($feature->Value2, $path.'.Value2', $result);
                break;

            default:
                break;
        }
    }

    private function validateReturnOptions(ShipmentRow $shipment, ValidationResult $result): void
    {
        if ($shipment->ShippingDateTimeTo !== null && $shipment->ShippingDateTimeFrom === null) {
            $result->add('shipment.shipping_window', 'ShippingDateTimeFrom', 'ShippingDateTimeFrom is required when ShippingDateTimeTo is supplied.');
        }

        if ($shipment->ReturnModeID === ReturnOptions::AfterDays->value
            && ($shipment->ReturnDays === null || $shipment->ReturnDays <= 0)) {
            $result->add('shipment.return_days', 'ReturnDays', 'ReturnDays must be greater than zero when ReturnModeID is AfterDays.');
        }
    }

    private function validateWeightRequirements(ShipmentRow $shipment, ValidationResult $result): void
    {
        $product = $shipment->knownProduct();

        if ($product === null || ! $product->requiresWeight()) {
            return;
        }

        if ($shipment->ColloList === null || count($shipment->ColloList) === 0) {
            $result->add('shipment.weight_required', 'ColloList', 'This product requires at least one parcel with a weight.');

            return;
        }

        foreach ($shipment->ColloList as $index => $collo) {
            if ($collo->Weight === null || $collo->Weight <= 0) {
                $result->add('shipment.weight_required', sprintf('ColloList.%s.Weight', $index), 'A positive parcel weight is required for this product.');
            }
        }
    }

    private function validateCustoms(ShipmentRow $shipment, ValidationResult $result): void
    {
        if (! $this->customsRequirementResolver->requiresCustoms($shipment->OUShipperAddress, $shipment->OURecipientAddress)) {
            return;
        }

        if (! $shipment->OURecipientAddress->hasPhoneOrEmail()) {
            $result->add('10074', 'OURecipientAddress', 'Customs shipments require a recipient phone number or email address.');
        }

        if ($shipment->OUShipperAddress !== null && ! $shipment->OUShipperAddress->hasPhoneOrEmail()) {
            $result->add('10075', 'OUShipperAddress', 'Customs shipments require a sender phone number or email address.');
        }

        if ($shipment->ColloList === null || count($shipment->ColloList) === 0) {
            $result->add('10076', 'ColloList', 'Customs shipments require parcel article data.');

            return;
        }

        $currencies = [];

        foreach ($shipment->ColloList as $colloIndex => $collo) {
            if ($collo->ColloArticleList === null || count($collo->ColloArticleList) === 0) {
                $result->add('10076', sprintf('ColloList.%s.ColloArticleList', $colloIndex), 'Every parcel in a customs shipment requires at least one article.');

                continue;
            }

            foreach ($collo->ColloArticleList as $articleIndex => $article) {
                if (! $article instanceof ColloArticleRow) {
                    continue;
                }

                $path = sprintf('ColloList.%s.ColloArticleList.%s', $colloIndex, $articleIndex);
                $this->validateCustomsArticle($article, $path, $currencies, $result);
            }
        }

        if (count(array_unique($currencies)) > 1) {
            $result->add('10077', 'ColloList.*.ColloArticleList.*.CurrencyID', 'All customs articles in a shipment must use the same currency.');
        }
    }

    /** @param list<string> $currencies */
    private function validateCustomsArticle(ColloArticleRow $article, string $path, array &$currencies, ValidationResult $result): void
    {
        $this->requireValue($article->ArticleName, $path.'.ArticleName', $result);

        if ($article->CustomsOptionID === CustomsOptions::Dokumente) {
            return;
        }

        if ($article->Quantity === null || $article->Quantity <= 0) {
            $result->add('10076', $path.'.Quantity', 'Quantity must be greater than zero for non-document customs articles.');
        }

        if ($article->UnitID === null) {
            $result->add('10076', $path.'.UnitID', 'UnitID is required for non-document customs articles.');
        }

        $hsTariff = $article->HSTariffNumber === null ? '' : (string) $article->HSTariffNumber;
        if (! preg_match('/^\d{6,10}$/', $hsTariff)) {
            $result->add('10079', $path.'.HSTariffNumber', 'HSTariffNumber must contain 6 to 10 digits.');
        }

        if (! preg_match('/^[A-Z]{2}$/', strtoupper((string) $article->CountryOfOriginID))) {
            $result->add('10076', $path.'.CountryOfOriginID', 'CountryOfOriginID must be a two-letter ISO country code.');
        }

        if ($article->ValueOfGoodsPerUnit === null || $article->ValueOfGoodsPerUnit < 0) {
            $result->add('10076', $path.'.ValueOfGoodsPerUnit', 'ValueOfGoodsPerUnit must be zero or greater.');
        }

        if (! preg_match('/^[A-Z]{3}$/', strtoupper((string) $article->CurrencyID))) {
            $result->add('10076', $path.'.CurrencyID', 'CurrencyID must be a three-letter currency code.');
        } else {
            $currencies[] = strtoupper((string) $article->CurrencyID);
        }

        if ($article->ConsumerUnitNetWeight === null || $article->ConsumerUnitNetWeight <= 0) {
            $result->add('10076', $path.'.ConsumerUnitNetWeight', 'ConsumerUnitNetWeight must be greater than zero.');
        }
    }

    private function requireValue(?string $value, string $path, ValidationResult $result): void
    {
        if (! $this->hasValue($value)) {
            $result->add('feature.value_required', $path, 'A value is required.');
        }
    }

    private function requireNumericValue(?string $value, string $path, ValidationResult $result): void
    {
        if (! $this->hasValue($value) || ! ctype_digit((string) $value)) {
            $result->add('feature.numeric_value_required', $path, 'A numeric value is required.');
        }
    }

    private function requirePositiveAmount(?string $value, string $path, ValidationResult $result): void
    {
        if (! is_numeric($value) || (float) $value <= 0) {
            $result->add('10067', $path, 'A positive amount is required.');
        }
    }

    private function requireCurrency(?string $value, string $path, ValidationResult $result): void
    {
        if (! preg_match('/^[A-Z]{3}$/', strtoupper((string) $value))) {
            $result->add('10068', $path, 'A three-letter currency code is required.');
        }
    }

    private function requirePipeParts(?string $value, int $parts, string $path, ValidationResult $result): void
    {
        $split = array_map('trim', explode('|', (string) $value));

        if (count($split) !== $parts || in_array('', $split, true)) {
            $result->add('10037', $path, sprintf('Value must contain %d non-empty pipe-separated parts.', $parts));
        }
    }

    private function hasValue(?string $value): bool
    {
        return $value !== null && trim($value) !== '';
    }
}
