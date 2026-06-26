<?php

namespace Tests\Unit;

use App\Services\Payments\Exceptions\UnsupportedPaymentGatewayException;
use App\Services\Payments\Gateways\CreditCardGateway;
use App\Services\Payments\Gateways\PaypalGateway;
use App\Services\Payments\PaymentGatewayFactory;
use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;

class PaymentGatewayFactoryTest extends TestCase
{
    private function factory(): PaymentGatewayFactory
    {
        return new PaymentGatewayFactory(new Container(), [
            'credit_card' => ['driver' => CreditCardGateway::class],
            'paypal' => ['driver' => PaypalGateway::class],
            'broken' => ['driver' => null],
        ]);
    }

    public function test_it_resolves_the_credit_card_gateway(): void
    {
        $gateway = $this->factory()->make('credit_card');

        $this->assertInstanceOf(CreditCardGateway::class, $gateway);
        $this->assertSame('credit_card', $gateway->identifier());
    }

    public function test_it_resolves_the_paypal_gateway(): void
    {
        $gateway = $this->factory()->make('paypal');

        $this->assertInstanceOf(PaypalGateway::class, $gateway);
        $this->assertSame('paypal', $gateway->identifier());
    }

    public function test_it_throws_for_an_unknown_method(): void
    {
        $this->expectException(UnsupportedPaymentGatewayException::class);

        $this->factory()->make('bitcoin');
    }

    public function test_it_throws_for_a_method_without_a_driver(): void
    {
        $this->expectException(UnsupportedPaymentGatewayException::class);

        $this->factory()->make('broken');
    }

    public function test_supported_methods_excludes_entries_without_a_driver(): void
    {
        $this->assertEqualsCanonicalizing(
            ['credit_card', 'paypal'],
            $this->factory()->supportedMethods(),
        );
    }
}
