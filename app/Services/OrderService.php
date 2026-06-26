<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\User;
use App\Services\Contracts\OrderServiceInterface;
use App\Services\Orders\OrderTotalCalculator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OrderService implements OrderServiceInterface
{
    public function __construct(protected OrderTotalCalculator $totals)
    {
    }

    /**
     * Paginated list of a user's orders, optionally filtered by status.
     */
    public function paginateForUser(User $user, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        return $user->orders()
            ->with(['items', 'payments'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Create an order with its items, computing the total server-side.
     *
     * @param  array{customer_name:string, customer_email:string, status?:string, items: array<int, array{product_name:string, quantity:int, price:float}>}  $data
     */
    public function create(User $user, array $data): Order
    {
        return DB::transaction(function () use ($user, $data) {
            $order = $user->orders()->create([
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'],
                'status' => $data['status'] ?? 'pending',
                'total' => 0,
            ]);

            $order->items()->createMany($data['items']);

            $order->update(['total' => $this->totals->forItems($data['items'])]);

            return $order->load(['items', 'payments']);
        });
    }

    /**
     * Update order attributes and, when items are supplied, replace them and
     * recompute the total. Never trusts a client-supplied total.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Order $order, array $data): Order
    {
        return DB::transaction(function () use ($order, $data) {
            $order->fill(array_filter([
                'customer_name' => $data['customer_name'] ?? null,
                'customer_email' => $data['customer_email'] ?? null,
                'status' => $data['status'] ?? null,
            ], fn ($value) => $value !== null));

            if (array_key_exists('items', $data)) {
                $order->items()->delete();
                $order->items()->createMany($data['items']);
                $order->total = $this->totals->forItems($data['items']);
            }

            $order->save();

            return $order->load(['items', 'payments']);
        });
    }

    /**
     * Delete an order, enforcing the rule that orders with payments cannot be deleted.
     *
     * @throws BusinessRuleException
     */
    public function delete(Order $order): void
    {
        if ($order->payments()->exists()) {
            throw new BusinessRuleException('Order cannot be deleted because it has associated payments.');
        }

        $order->delete();
    }
}
