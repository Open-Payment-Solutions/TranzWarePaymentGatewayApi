<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions;

use RuntimeException;
use Throwable;

class OrderStatusRetrieveException extends RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Unable to retrieve order status.', 0, $previous);
    }
}
