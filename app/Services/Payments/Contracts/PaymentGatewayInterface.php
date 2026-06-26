<?php

namespace App\Services\Payments\Contracts;

use App\Models\Payment;
use App\Services\Payments\PaymentResult;

/**
 * Strategy contract. Every payment gateway implements this single method.
 *
 * To add a new gateway:
 *   1. Create a class implementing this interface.
 *   2. Map a payment method to it in config/payments.php.
 *   3. Add any credentials to .env / config/payments.php.
 * No controller or service changes are required.
 */
interface PaymentGatewayInterface
{
    /**
     * Attempt to process the given payment and return a uniform result.
     */
    public function process(Payment $payment): PaymentResult;

    /**
     * Machine identifier of the gateway (e.g. "credit_card", "paypal").
     */
    public function identifier(): string;
}
