<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with a demo user and orders.
     */
    public function run(): void
    {
        // Demo user for exploring the API (matches the Postman collection).
        $user = User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@tocaan.test',
            'password' => 'password',
        ]);

        // A confirmed order (payable) with items.
        $confirmed = Order::factory()->for($user)->confirmed()->create([
            'customer_name' => 'Demo User',
            'customer_email' => 'demo@tocaan.test',
        ]);
        $confirmed->items()->createMany([
            ['product_name' => 'Wireless Mouse', 'quantity' => 2, 'price' => 25.00],
            ['product_name' => 'Mechanical Keyboard', 'quantity' => 1, 'price' => 90.00],
        ]);
        $confirmed->update(['total' => 140.00]);

        // A pending order (not payable) for demonstrating the 409 rule.
        $pending = Order::factory()->for($user)->create([
            'customer_name' => 'Demo User',
            'customer_email' => 'demo@tocaan.test',
            'status' => OrderStatus::Pending,
        ]);
        $pending->items()->create(
            ['product_name' => 'USB-C Cable', 'quantity' => 3, 'price' => 10.00]
        );
        $pending->update(['total' => 30.00]);
    }
}
