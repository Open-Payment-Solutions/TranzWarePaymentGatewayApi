<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions;

use InvalidArgumentException;

class InvalidOrderId extends InvalidArgumentException
{
    public function __construct()
    {
        parent::__construct('Invalid order ID');
    }
}
