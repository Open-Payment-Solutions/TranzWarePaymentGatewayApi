<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions;

use RuntimeException;
use Throwable;

class OrderStatusRetrieveException extends RuntimeException
{
    /**
     * Create a status retrieval exception with its original cause.
     *
     * @param Throwable|null $previous
     */
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Unable to retrieve order status.', 0, $previous);
    }
}
