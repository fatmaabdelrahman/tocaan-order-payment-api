<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create();
    }

    public function test_it_creates_an_order_and_computes_the_total_server_side(): void
    {
        $response = $this->actingAs($this->user(), 'api')->postJson('/api/orders', [
            'customer_name' => 'Acme Corp',
            'customer_email' => 'buyer@acme.test',
            'items' => [
                ['product_name' => 'Widget', 'quantity' => 3, 'price' => 10],
                ['product_name' => 'Gadget', 'quantity' => 1, 'price' => 20.5],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.total', 50.5)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonCount(2, 'data.items');
    }

    public function test_it_validates_order_creation(): void
    {
        $this->actingAs($this->user(), 'api')
            ->postJson('/api/orders', ['customer_name' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['customer_name', 'customer_email', 'items']);
    }

    public function test_it_lists_orders_paginated(): void
    {
        $user = $this->user();
        Order::factory()->count(3)->for($user)->create();

        $this->actingAs($user, 'api')
            ->getJson('/api/orders')
            ->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta'])
            ->assertJsonCount(3, 'data');
    }

    public function test_it_caps_the_page_size(): void
    {
        $user = $this->user();
        Order::factory()->count(3)->for($user)->create();

        // An absurd per_page must be clamped to the 100 maximum, not honoured verbatim.
        $this->actingAs($user, 'api')
            ->getJson('/api/orders?per_page=100000')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 100);
    }

    public function test_it_filters_orders_by_status(): void
    {
        $user = $this->user();
        Order::factory()->for($user)->confirmed()->create();
        Order::factory()->count(2)->for($user)->create(); // pending

        $this->actingAs($user, 'api')
            ->getJson('/api/orders?status=confirmed')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_it_updates_an_order_and_recomputes_total(): void
    {
        $user = $this->user();
        $order = Order::factory()->for($user)->create(['total' => 0]);

        $this->actingAs($user, 'api')
            ->putJson("/api/orders/{$order->id}", [
                'status' => 'confirmed',
                'items' => [['product_name' => 'New', 'quantity' => 2, 'price' => 15]],
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed')
            ->assertJsonPath('data.total', 30);
    }

    public function test_it_deletes_an_order_without_payments(): void
    {
        $user = $this->user();
        $order = Order::factory()->for($user)->create();

        $this->actingAs($user, 'api')
            ->deleteJson("/api/orders/{$order->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }

    public function test_it_blocks_deleting_an_order_with_payments(): void
    {
        $user = $this->user();
        $order = Order::factory()->for($user)->confirmed()->create();
        Payment::factory()->for($order)->create();

        $this->actingAs($user, 'api')
            ->deleteJson("/api/orders/{$order->id}")
            ->assertStatus(409);

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }

    public function test_a_user_cannot_view_another_users_order(): void
    {
        $order = Order::factory()->for($this->user())->create();

        $this->actingAs($this->user(), 'api')
            ->getJson("/api/orders/{$order->id}")
            ->assertForbidden();
    }

    public function test_a_user_cannot_update_another_users_order(): void
    {
        $order = Order::factory()->for($this->user())->create();

        $this->actingAs($this->user(), 'api')
            ->putJson("/api/orders/{$order->id}", ['status' => 'confirmed'])
            ->assertForbidden();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending']);
    }

    public function test_a_user_cannot_delete_another_users_order(): void
    {
        $order = Order::factory()->for($this->user())->create();

        $this->actingAs($this->user(), 'api')
            ->deleteJson("/api/orders/{$order->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }
}
