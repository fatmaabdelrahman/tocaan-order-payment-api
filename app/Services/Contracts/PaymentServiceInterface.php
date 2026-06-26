<?php

namespace App\Services\Contracts;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Abstraction the HTTP layer depends on (Dependency Inversion). Controllers talk
 * to this contract, not the concrete PaymentService.
 */
interface PaymentServiceInterface
{
    /**
     * Process a payment for an order via the appropriate gateway strategy.
     */
    public function process(Order $order, string $method): Payment;

    /**
     * Paginated payments across all of a user's orders.
     */
    public function paginateForUser(User $user, int $perPage = 15): LengthAwarePaginator;

    /**
     * Paginated payments for a single order.
     */
    public function paginateForOrder(Order $order, int $perPage = 15): LengthAwarePaginator;
}
