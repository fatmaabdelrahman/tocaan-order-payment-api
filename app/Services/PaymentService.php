<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Contracts\PaymentServiceInterface;
use App\Services\Payments\PaymentGatewayFactory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PaymentService implements PaymentServiceInterface
{
    public function __construct(protected PaymentGatewayFactory $gatewayFactory)
    {
    }

    /**
     * Process a payment for an order using the appropriate gateway strategy.
     *
     * Business rule: only orders in the `confirmed` status may be paid for.
     * The charged amount is always the order's server-computed total.
     *
     * @throws BusinessRuleException  when the order is not payable (rendered 409).
     */
    public function process(Order $order, string $method): Payment
    {
        if (! $order->isPayable()) {
            throw new BusinessRuleException(
                'Payments can only be processed for orders in the confirmed status.'
            );
        }

        // Resolve the gateway up front so an unsupported method surfaces clearly.
        $gateway = $this->gatewayFactory->make($method);

        // 1. Persist the attempt as `pending` and commit it immediately, so the
        //    record of the attempt survives even if the next steps fail.
        $payment = $order->payments()->create([
            'status' => PaymentStatus::Pending,
            'method' => $method,
            'amount' => $order->total,
        ]);

        // 2. Call the gateway OUTSIDE any DB transaction. For real gateways this is
        //    a network round-trip; holding a transaction open across external I/O
        //    would keep row locks and a DB connection tied up for its duration.
        $result = $gateway->process($payment);

        // 3. Record the outcome.
        $payment->update([
            'status' => $result->successful ? PaymentStatus::Successful : PaymentStatus::Failed,
            'transaction_reference' => $result->reference,
        ]);

        return $payment->refresh();
    }

    public function paginateForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return Payment::query()
            ->whereHas('order', fn ($query) => $query->where('user_id', $user->id))
            ->latest()
            ->paginate($perPage);
    }

    public function paginateForOrder(Order $order, int $perPage = 15): LengthAwarePaginator
    {
        return $order->payments()->latest()->paginate($perPage);
    }
}
