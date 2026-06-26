<?php

namespace App\Services\Payments\Exceptions;

use RuntimeException;

class UnsupportedPaymentGatewayException extends RuntimeException
{
    public static function forMethod(string $method): self
    {
        return new self("No payment gateway is configured for method [{$method}].");
    }
}
