<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions;

use InvalidArgumentException;

class InvalidSessionId extends InvalidArgumentException
{
    public function __construct()
    {
        parent::__construct('Invalid session ID');
    }
}
