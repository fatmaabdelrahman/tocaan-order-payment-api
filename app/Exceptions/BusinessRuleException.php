<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Thrown when a domain rule is violated (e.g. paying for a non-confirmed order,
 * deleting an order that has payments). Rendered as 409 Conflict.
 */
class BusinessRuleException extends HttpException
{
    public function __construct(string $message)
    {
        parent::__construct(Response::HTTP_CONFLICT, $message);
    }
}
