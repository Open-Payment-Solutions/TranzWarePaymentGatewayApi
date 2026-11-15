<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions;

use InvalidArgumentException;

class EmptyXMLMessage extends InvalidArgumentException
{
    public function __construct()
    {
        parent::__construct('Empty XML message');
    }
}
