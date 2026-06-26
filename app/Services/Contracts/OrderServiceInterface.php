<?php

namespace App\Services\Contracts;

use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Abstraction the HTTP layer depends on (Dependency Inversion). Controllers and
 * other consumers talk to this contract, not the concrete OrderService.
 */
interface OrderServiceInterface
{
    public function paginateForUser(User $user, ?string $status = null, int $perPage = 15): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): Order;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Order $order, array $data): Order;

    public function delete(Order $order): void;
}
