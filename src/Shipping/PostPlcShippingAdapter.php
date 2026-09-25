<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Shipping;

use AlexanderPoellmann\LaravelPostPlc\Classes\Collo;
use AlexanderPoellmann\LaravelPostPlc\Classes\Shipment as PostShipment;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\AddressRow;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\ColloRow;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\ImportShipmentResult;
use AlexanderPoellmann\LaravelPostPlc\Enums\LabelSizes;
use AlexanderPoellmann\LaravelPostPlc\Enums\PaperLayouts;
use AlexanderPoellmann\LaravelPostPlc\Enums\PostProductCodes;
use AlexanderPoellmann\LaravelPostPlc\Enums\PrinterEncoding;
use AlexanderPoellmann\LaravelPostPlc\Enums\PrinterLanguages;
use AlexanderPoellmann\LaravelPostPlc\Enums\ServiceMethods;
use AlexanderPoellmann\LaravelPostPlc\Exceptions\PlcRequestException;
use AlexanderPoellmann\LaravelPostPlc\LaravelPostPlc;
use AlexanderPoellmann\LaravelPostPlc\Validation\ShipmentValidator;
use AlexanderPoellmann\LaravelPostPlc\ValueObjects\ProductCode;
use AlexanderPoellmann\Shipping\Contracts\Carrier;
use AlexanderPoellmann\Shipping\Contracts\CreatesShipments;
use AlexanderPoellmann\Shipping\Data\Address;
use AlexanderPoellmann\Shipping\Data\Label;
use AlexanderPoellmann\Shipping\Data\Parcel;
use AlexanderPoellmann\Shipping\Data\Shipment;
use AlexanderPoellmann\Shipping\Data\ShipmentResult;
use AlexanderPoellmann\Shipping\Data\TrackingNumber;
use InvalidArgumentException;
use LogicException;

final readonly class PostPlcShippingAdapter implements Carrier, CreatesShipments
{
    private ?ProductCode $product;

    private ShipmentValidator $validator;

    public function __construct(
        private LaravelPostPlc $client,
        PostProductCodes|ProductCode|string|null $product = null,
        private PrinterLanguages $printerLanguage = PrinterLanguages::PDF,
        private LabelSizes $labelSize = LabelSizes::LONG,
        private PaperLayouts $paperLayout = PaperLayouts::A5T,
        private PrinterEncoding $encoding = PrinterEncoding::UTF,
        ?ShipmentValidator $validator = null,
    ) {
        if (! in_array($printerLanguage, [PrinterLanguages::PDF, PrinterLanguages::ZPL2, PrinterLanguages::PDFZPL2, PrinterLanguages::None], true)) {
            throw new InvalidArgumentException('The shipping adapter supports PDF, ZPL2, PDFZPL2 and None. Use the native ImportShipmentReturnImage operation for image labels.');
        }

        $this->product = $product === null ? null : ProductCode::from($product);
        $this->validator = $validator ?? app(ShipmentValidator::class);
    }

    public function carrier(): string
    {
        return 'post-plc';
    }

    public function forProduct(PostProductCodes|ProductCode|string $product): self
    {
        return new self(
            $this->client,
            $product,
            $this->printerLanguage,
            $this->labelSize,
            $this->paperLayout,
            $this->encoding,
            $this->validator,
        );
    }

    public function withPrinter(
        PrinterLanguages $language = PrinterLanguages::PDF,
        LabelSizes $labelSize = LabelSizes::LONG,
        PaperLayouts $paperLayout = PaperLayouts::A5T,
        PrinterEncoding $encoding = PrinterEncoding::UTF,
    ): self {
        return new self($this->client, $this->product, $language, $labelSize, $paperLayout, $encoding, $this->validator);
    }

    public function createShipment(Shipment $shipment): ShipmentResult
    {
        if ($this->product === null) {
            throw new LogicException('Configure an Austrian Post product with forProduct() before creating a shipment.');
        }

        $native = (new PostShipment($this->client->configuration()))
            ->using($this->product)
            ->withPrinter($this->printerLanguage, $this->labelSize, $this->paperLayout, $this->encoding)
            ->from($this->address($shipment->sender))
            ->to($this->address($shipment->recipient))
            ->parcels(array_map($this->parcel(...), $shipment->parcels));

        if ($shipment->reference !== null) {
            $native->withNumber($shipment->reference)->shipperReferences($shipment->reference);
        }

        if ($shipment->shippingDate !== null) {
            $native->shippingWindow($shipment->shippingDate);
        }

        $row = $native->get();
        $this->validator->validate($row)->throwIfInvalid();

        $this->client->request(ServiceMethods::ImportShipment, $row, asRow: true);
        $result = $this->client->toData(ImportShipmentResult::class);

        if ($result->errorCode !== null && $result->errorCode !== '') {
            throw new PlcRequestException(ServiceMethods::ImportShipment, $result->errorCode, $result->errorMessage);
        }

        $trackingNumbers = $this->trackingNumbers($result);
        $labels = $this->labels($result, $trackingNumbers);

        return new ShipmentResult($trackingNumbers, $labels);
    }

    private function address(Address $address): AddressRow
    {
        return new AddressRow(
            ThirdPartyID: null,
            VatId: null,
            Name1: $address->name,
            Name2: $address->name2,
            Name3: $address->contactPerson,
            Name4: null,
            AddressLine1: $address->street,
            HouseNumber: $address->houseNumber,
            AddressLine2: $address->additional,
            PostalCode: $address->postalCode,
            CountryID: strtoupper($address->countryCode),
            City: $address->city,
            Tel1: $address->phone,
            Tel2: null,
            Fax: null,
            Email: $address->email,
            Homepage: null,
            EORINumber: null,
            PersonalTaxNumber: null,
        );
    }

    private function parcel(Parcel $parcel): ColloRow
    {
        $native = new Collo;

        if ($parcel->weightInGrams !== null) {
            $native->weight($parcel->weightInGrams / 1000);
        }

        if ($parcel->lengthInMillimeters !== null) {
            $native
                ->length((int) ceil($parcel->lengthInMillimeters / 10))
                ->width((int) ceil($parcel->widthInMillimeters / 10))
                ->height((int) ceil($parcel->heightInMillimeters / 10));
        }

        return $native->get();
    }

    /** @return list<TrackingNumber> */
    private function trackingNumbers(ImportShipmentResult $result): array
    {
        $trackingNumbers = [];

        if ($result->ImportShipmentResult === null) {
            return [];
        }

        foreach ($result->ImportShipmentResult as $collo) {
            if ($collo->ColloCodeList === null || count($collo->ColloCodeList) === 0) {
                continue;
            }

            // PLC may return several carrier codes per parcel. Expose the first
            // usable code without representing aliases as additional parcels.
            foreach ($collo->ColloCodeList as $code) {
                if (trim($code->Code) !== '') {
                    $trackingNumbers[] = new TrackingNumber($code->Code);
                    break;
                }
            }
        }

        return $trackingNumbers;
    }

    /**
     * @param  list<TrackingNumber>  $trackingNumbers
     * @return list<Label>
     */
    private function labels(ImportShipmentResult $result, array $trackingNumbers): array
    {
        // PDF and ZPL are shipment-level documents and may cover several parcels.
        $trackingNumber = count($result->ImportShipmentResult ?? []) === 1 && count($trackingNumbers) === 1
            ? $trackingNumbers[0]
            : null;
        $labels = [];

        if ($result->pdfData !== null && $result->pdfData !== '') {
            $contents = base64_decode($result->pdfData, true);

            if ($contents === false || $contents === '') {
                throw new PlcRequestException(ServiceMethods::ImportShipment, 'invalid_label_data', 'PLC returned invalid base64 PDF label data.');
            }

            $labels[] = new Label(
                trackingNumber: $trackingNumber,
                contents: $contents,
                mimeType: 'application/pdf',
                format: 'pdf',
            );
        }

        if ($result->zplLabelData !== null && $result->zplLabelData !== '') {
            $labels[] = new Label(
                trackingNumber: $trackingNumber,
                contents: $result->zplLabelData,
                mimeType: 'application/zpl',
                format: 'zpl',
            );
        }

        return $labels;
    }
}
