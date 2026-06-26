<?php

namespace App\Services\Payments\Gateways;

class PaypalGateway extends AbstractSimulatedGateway
{
    public function identifier(): string
    {
        return 'paypal';
    }

    protected function label(): string
    {
        return 'PayPal gateway';
    }

    protected function referencePrefix(): string
    {
        return 'PP-';
    }
}
