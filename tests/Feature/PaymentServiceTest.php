<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): PaymentService
    {
        return app(PaymentService::class);
    }

    private function confirmedOrder(): Order
    {
        return Order::factory()->for(User::factory())->confirmed()->create(['total' => 100]);
    }

    public function test_a_successful_payment_is_persisted_with_a_reference(): void
    {
        $payment = $this->service()->process($this->confirmedOrder(), 'credit_card');

        // Must end resolved — never left stuck on the initial `pending` state.
        $this->assertSame(PaymentStatus::Successful, $payment->status);
        $this->assertNotNull($payment->transaction_reference);
        $this->assertSame('100.00', $payment->amount);
    }

    public function test_a_forced_decline_is_persisted_as_failed_without_a_reference(): void
    {
        config(['payments.gateways.credit_card.fake_outcome' => 'failed']);

        $payment = $this->service()->process($this->confirmedOrder(), 'credit_card');

        $this->assertSame(PaymentStatus::Failed, $payment->status);
        $this->assertNull($payment->transaction_reference);
    }

    public function test_it_refuses_to_process_a_non_confirmed_order(): void
    {
        $order = Order::factory()->for(User::factory())->create(); // pending

        $this->expectException(BusinessRuleException::class);

        $this->service()->process($order, 'credit_card');
    }
}
