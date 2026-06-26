<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\StoreOrderRequest;
use App\Http\Requests\Orders\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\Contracts\OrderServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class OrderController extends Controller
{
    /** Upper bound for client-requested page size. */
    private const MAX_PER_PAGE = 100;

    public function __construct(protected OrderServiceInterface $orders)
    {
    }

    /**
     * GET /api/orders?status=&per_page=  — paginated, filterable by status.
     */
    public function index(Request $request): ResourceCollection
    {
        $perPage = min(max((int) $request->query('per_page', 15), 1), self::MAX_PER_PAGE);

        $orders = $this->orders->paginateForUser(
            $request->user(),
            $request->query('status'),
            $perPage,
        );

        return OrderResource::collection($orders);
    }

    /**
     * POST /api/orders — create an order with items.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orders->create($request->user(), $request->validated());

        return OrderResource::make($order)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /api/orders/{order}
     */
    public function show(Order $order): OrderResource
    {
        $this->authorize('view', $order);

        return OrderResource::make($order->load(['items', 'payments']));
    }

    /**
     * PUT/PATCH /api/orders/{order}
     */
    public function update(UpdateOrderRequest $request, Order $order): OrderResource
    {
        $this->authorize('update', $order);

        return OrderResource::make($this->orders->update($order, $request->validated()));
    }

    /**
     * DELETE /api/orders/{order} — blocked if the order has payments (409).
     */
    public function destroy(Order $order): JsonResponse
    {
        $this->authorize('delete', $order);

        $this->orders->delete($order);

        return response()->json(null, 204);
    }
}
