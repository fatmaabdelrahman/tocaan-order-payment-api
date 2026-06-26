<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\ProcessPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Services\Contracts\PaymentServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PaymentController extends Controller
{
    public function __construct(protected PaymentServiceInterface $payments)
    {
    }

    /**
     * GET /api/payments — all payments for the authenticated user's orders (paginated).
     */
    public function index(Request $request): ResourceCollection
    {
        return PaymentResource::collection(
            $this->payments->paginateForUser($request->user())
        );
    }

    /**
     * GET /api/orders/{order}/payments — payments for a specific order.
     */
    public function indexForOrder(Order $order): ResourceCollection
    {
        $this->authorize('view', $order);

        return PaymentResource::collection($this->payments->paginateForOrder($order));
    }

    /**
     * POST /api/orders/{order}/payments — process a payment via the appropriate gateway.
     *
     * Returns 201 on a recorded attempt. A simulated decline still records a
     * payment (status=failed) and returns 201 with that payload; the 409 path is
     * reserved for the business-rule violation (non-confirmed order).
     */
    public function store(ProcessPaymentRequest $request, Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        $payment = $this->payments->process($order, $request->validated()['method']);

        return PaymentResource::make($payment)
            ->response()
            ->setStatusCode(201);
    }
}
