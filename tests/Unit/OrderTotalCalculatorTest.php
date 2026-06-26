<?php

namespace Tests\Unit;

use App\Services\Orders\OrderTotalCalculator;
use PHPUnit\Framework\TestCase;

class OrderTotalCalculatorTest extends TestCase
{
    public function test_it_sums_quantity_times_price(): void
    {
        $total = (new OrderTotalCalculator())->forItems([
            ['quantity' => 3, 'price' => 10],
            ['quantity' => 1, 'price' => 20.5],
        ]);

        $this->assertSame(50.5, $total);
    }

    public function test_it_returns_zero_for_no_items(): void
    {
        $this->assertSame(0.0, (new OrderTotalCalculator())->forItems([]));
    }

    public function test_it_handles_string_numeric_input(): void
    {
        $total = (new OrderTotalCalculator())->forItems([
            ['quantity' => '2', 'price' => '5.25'],
        ]);

        $this->assertSame(10.5, $total);
    }
}
