<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Requests;

function fopen(string $path, string $mode)
{
    return \fopen('php://temp', $mode);
}
