<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private function confirmedOrder(User $user): Order
    {
        $order = Order::factory()->for($user)->confirmed()->create(['total' => 100]);
        $order->items()->create(['product_name' => 'Item', 'quantity' => 1, 'price' => 100]);

        return $order;
    }

    public function test_it_processes_a_payment_for_a_confirmed_order(): void
    {
        $user = User::factory()->create();
        $order = $this->confirmedOrder($user);

        $this->actingAs($user, 'api')
            ->postJson("/api/orders/{$order->id}/payments", ['method' => 'credit_card'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'successful')
            ->assertJsonPath('data.amount', 100)
            ->assertJsonPath('data.method', 'credit_card');

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => 'successful',
        ]);
    }

    public function test_it_blocks_payment_for_a_non_confirmed_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create(); // pending

        $this->actingAs($user, 'api')
            ->postJson("/api/orders/{$order->id}/payments", ['method' => 'credit_card'])
            ->assertStatus(409);

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_it_rejects_an_unsupported_payment_method(): void
    {
        $user = User::factory()->create();
        $order = $this->confirmedOrder($user);

        $this->actingAs($user, 'api')
            ->postJson("/api/orders/{$order->id}/payments", ['method' => 'bitcoin'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['method']);
    }

    public function test_it_records_a_failed_payment_when_outcome_is_forced(): void
    {
        config(['payments.gateways.credit_card.fake_outcome' => 'failed']);

        $user = User::factory()->create();
        $order = $this->confirmedOrder($user);

        $this->actingAs($user, 'api')
            ->postJson("/api/orders/{$order->id}/payments", ['method' => 'credit_card'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'failed');
    }

    public function test_it_lists_payments_for_an_order(): void
    {
        $user = User::factory()->create();
        $order = $this->confirmedOrder($user);

        $this->actingAs($user, 'api')
            ->postJson("/api/orders/{$order->id}/payments", ['method' => 'paypal'])
            ->assertCreated();

        $this->actingAs($user, 'api')
            ->getJson("/api/orders/{$order->id}/payments")
            ->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta'])
            ->assertJsonCount(1, 'data');
    }
}
