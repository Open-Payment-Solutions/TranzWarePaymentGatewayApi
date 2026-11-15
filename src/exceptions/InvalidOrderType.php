<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions;

use InvalidArgumentException;

class InvalidOrderType extends InvalidArgumentException
{
    /**
     * Create an exception for an unsupported order type.
     *
     * @param string $orderType
     */
    public function __construct(string $orderType)
    {
        parent::__construct("Invalid order type: $orderType");
    }
}
