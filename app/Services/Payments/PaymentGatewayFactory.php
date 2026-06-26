<?php

namespace App\Services\Payments;

use App\Services\Payments\Contracts\PaymentGatewayInterface;
use App\Services\Payments\Exceptions\UnsupportedPaymentGatewayException;
use Illuminate\Contracts\Container\Container;

/**
 * Resolves the correct payment gateway strategy for a given payment method,
 * using the registry in config/payments.php. This is the single place that maps
 * a method string to a concrete gateway, keeping the rest of the app gateway-agnostic.
 */
class PaymentGatewayFactory
{
    /**
     * @param  array<string, array<string, mixed>>  $gateways  Registry from config('payments.gateways').
     */
    public function __construct(
        protected Container $container,
        protected array $gateways,
    ) {
    }

    /**
     * @throws UnsupportedPaymentGatewayException
     */
    public function make(string $method): PaymentGatewayInterface
    {
        $config = $this->gateways[$method] ?? null;

        if ($config === null || empty($config['driver'])) {
            throw UnsupportedPaymentGatewayException::forMethod($method);
        }

        /** @var class-string<PaymentGatewayInterface> $driver */
        $driver = $config['driver'];

        return $this->container->make($driver, ['config' => $config]);
    }

    /**
     * Methods that currently have a configured gateway (used for validation).
     *
     * @return list<string>
     */
    public function supportedMethods(): array
    {
        return array_keys(array_filter(
            $this->gateways,
            fn ($config) => ! empty($config['driver'])
        ));
    }
}
