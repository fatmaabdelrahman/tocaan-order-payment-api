<?php

namespace Tests\Unit;

use App\Models\Payment;
use App\Services\Payments\Gateways\CreditCardGateway;
use App\Services\Payments\Gateways\PaypalGateway;
use PHPUnit\Framework\TestCase;

class PaymentGatewayTest extends TestCase
{
    public function test_credit_card_gateway_succeeds_by_default(): void
    {
        $result = (new CreditCardGateway())->process(new Payment());

        $this->assertTrue($result->successful);
        $this->assertStringStartsWith('CC-', $result->reference);
    }

    public function test_paypal_gateway_succeeds_by_default(): void
    {
        $result = (new PaypalGateway())->process(new Payment());

        $this->assertTrue($result->successful);
        $this->assertStringStartsWith('PP-', $result->reference);
    }

    public function test_gateway_fails_when_outcome_is_forced(): void
    {
        $gateway = new CreditCardGateway(['fake_outcome' => 'failed']);

        $result = $gateway->process(new Payment());

        $this->assertFalse($result->successful);
        $this->assertNull($result->reference);
    }
}
