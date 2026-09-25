<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Validation;

use AlexanderPoellmann\LaravelPostPlc\DataTransferObjects\PickupOrderRow;
use AlexanderPoellmann\LaravelPostPlc\Enums\PickupLocationTypes;
use AlexanderPoellmann\LaravelPostPlc\Enums\SecurePickupLocationTypes;

final class PickupOrderValidator
{
    public function validate(PickupOrderRow $order): ValidationResult
    {
        $result = new ValidationResult;

        if ($order->NumberOfPackages !== null && ($order->NumberOfPackages < 1 || $order->NumberOfPackages > 5)) {
            $result->add('10090', 'NumberOfPackages', 'NumberOfPackages must be between 1 and 5.');
        }

        if ($order->PickupLocationType === PickupLocationTypes::Secure && $order->SecurePickupLocationType === null) {
            $result->add('10093', 'SecurePickupLocationType', 'SecurePickupLocationType is required for secure pickup.');
        }

        if ($order->PickupLocationType === PickupLocationTypes::PersonalHandover && $order->SecurePickupLocationType !== null) {
            $result->add('10093', 'SecurePickupLocationType', 'SecurePickupLocationType must be omitted for personal handover.');
        }

        if ($order->SecurePickupLocationType === SecurePickupLocationTypes::Other
            && ($order->OtherSecurePickupLocation === null || trim($order->OtherSecurePickupLocation) === '')) {
            $result->add('10094', 'OtherSecurePickupLocation', 'OtherSecurePickupLocation is required when the secure pickup location is Other.');
        }

        if (! $order->AcceptTermsAndConditions) {
            $result->add('10096', 'AcceptTermsAndConditions', 'The pickup service terms and conditions must be accepted.');
        }

        if (trim($order->ContactPersonName) === '') {
            $result->add('pickup.contact_required', 'ContactPersonName', 'ContactPersonName is required.');
        }

        foreach (['Reference1' => $order->Reference1, 'Reference2' => $order->Reference2] as $field => $value) {
            if ($value !== null && mb_strlen($value) > 45) {
                $result->add('pickup.reference_too_long', $field, $field.' may not exceed 45 characters.');
            }
        }

        return $result;
    }
}
