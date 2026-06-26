<?php

namespace App\Services\Payments\Gateways;

class CreditCardGateway extends AbstractSimulatedGateway
{
    public function identifier(): string
    {
        return 'credit_card';
    }

    protected function label(): string
    {
        return 'Credit Card gateway';
    }

    protected function referencePrefix(): string
    {
        return 'CC-';
    }
}
