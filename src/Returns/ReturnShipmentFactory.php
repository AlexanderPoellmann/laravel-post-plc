<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Returns;

use AlexanderPoellmann\LaravelPostPlc\Classes\Shipment;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\AddressRow;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\ColloRow;
use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\ShipmentRow;
use AlexanderPoellmann\LaravelPostPlc\Enums\PostProductCodes;
use AlexanderPoellmann\LaravelPostPlc\LaravelPostPlc;
use AlexanderPoellmann\LaravelPostPlc\ValueObjects\ProductCode;
use InvalidArgumentException;

final readonly class ReturnShipmentFactory
{
    public function __construct(private LaravelPostPlc $client) {}

    /** @param list<ColloRow|array<string, mixed>> $parcels */
    public function fromAddresses(
        AddressRow $sender,
        AddressRow $recipient,
        PostProductCodes|ProductCode|string|null $product = null,
        array $parcels = [],
    ): ShipmentRow {
        $product ??= $this->defaultProduct($sender, $recipient);

        $shipment = (new Shipment($this->client->configuration()))
            ->withPrinter()
            ->using($product)
            ->from($sender)
            ->to($recipient);

        if ($parcels !== []) {
            $shipment->parcels($parcels);
        }

        return $shipment->get();
    }

    /** @param list<ColloRow|array<string, mixed>> $parcels */
    public function fromOutbound(
        ShipmentRow $outbound,
        AddressRow $returnRecipient,
        PostProductCodes|ProductCode|string|null $product = null,
        array $parcels = [],
    ): ShipmentRow {
        return $this->fromAddresses(
            sender: $outbound->OURecipientAddress,
            recipient: $returnRecipient,
            product: $product,
            parcels: $parcels,
        );
    }

    private function defaultProduct(AddressRow $sender, AddressRow $recipient): PostProductCodes
    {
        if ($sender->countryCode() === 'AT' && $recipient->countryCode() === 'AT') {
            return PostProductCodes::Retourpaket;
        }

        throw new InvalidArgumentException(
            'International returns require an explicit PLC return product because products 04, 63 and 66 depend on the customer contract and return workflow.',
        );
    }
}
