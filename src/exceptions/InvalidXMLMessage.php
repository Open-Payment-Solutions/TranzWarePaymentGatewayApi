<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions;

use InvalidArgumentException;

class InvalidXMLMessage extends InvalidArgumentException
{
    public function __construct()
    {
        parent::__construct('Invalid XML message');
    }
}
