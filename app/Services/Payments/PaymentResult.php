<?php

namespace App\Services\Payments;

/**
 * Immutable value object returned by every payment gateway so the caller has a
 * single, predictable contract regardless of which gateway processed the charge.
 */
final class PaymentResult
{
    public function __construct(
        public readonly bool $successful,
        public readonly ?string $reference = null,
        public readonly ?string $message = null,
    ) {
    }

    public static function success(string $reference, ?string $message = null): self
    {
        return new self(true, $reference, $message ?? 'Payment processed successfully.');
    }

    public static function failure(?string $message = null, ?string $reference = null): self
    {
        return new self(false, $reference, $message ?? 'Payment failed.');
    }
}
