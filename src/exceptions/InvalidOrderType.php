<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions;

use InvalidArgumentException;

class InvalidOrderType extends InvalidArgumentException
{
    public function __construct(string $orderType)
    {
        parent::__construct("Invalid order type: $orderType");
    }
}
