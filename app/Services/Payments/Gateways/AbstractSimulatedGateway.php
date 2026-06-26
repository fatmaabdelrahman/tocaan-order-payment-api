<?php

namespace App\Services\Payments\Gateways;

use App\Models\Payment;
use App\Services\Payments\Contracts\PaymentGatewayInterface;
use App\Services\Payments\PaymentResult;
use Illuminate\Support\Str;

/**
 * Shared simulation logic for take-home gateways. Real gateways would replace
 * simulateOutcome() with an actual API call to the provider's SDK.
 *
 * The simulated outcome is deterministic by default (success), but can be forced
 * to "failed" via the PAYMENT_FAKE_OUTCOME env var (config payments.fake_outcome)
 * so the failure path can be demonstrated and tested on demand.
 */
abstract class AbstractSimulatedGateway implements PaymentGatewayInterface
{
    /**
     * Per-gateway configuration (credentials, etc.) injected by the factory.
     *
     * @param  array<string, mixed>  $config
     */
    public function __construct(protected array $config = [])
    {
    }

    public function process(Payment $payment): PaymentResult
    {
        $forced = $this->config['fake_outcome'] ?? null;

        if ($forced === 'failed') {
            return PaymentResult::failure(
                sprintf('%s simulated a declined transaction.', $this->label())
            );
        }

        return PaymentResult::success(
            $this->referencePrefix().Str::upper(Str::random(12)),
            sprintf('%s processed the payment successfully.', $this->label())
        );
    }

    /** Human-friendly gateway name for messages. */
    abstract protected function label(): string;

    /** Prefix for the generated transaction reference. */
    abstract protected function referencePrefix(): string;
}
