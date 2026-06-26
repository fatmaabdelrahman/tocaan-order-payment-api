<?php

namespace App\Services\Orders;

/**
 * Single responsibility: turn a set of line items into an order total.
 * Isolated so pricing logic has one home and can be unit-tested on its own.
 */
class OrderTotalCalculator
{
    /**
     * Σ(quantity × price) for the supplied line items.
     *
     * @param  array<int, array{quantity:int|string, price:float|string}>  $items
     */
    public function forItems(array $items): float
    {
        return array_reduce(
            $items,
            fn (float $carry, array $item) => $carry + ((int) $item['quantity'] * (float) $item['price']),
            0.0
        );
    }
}
