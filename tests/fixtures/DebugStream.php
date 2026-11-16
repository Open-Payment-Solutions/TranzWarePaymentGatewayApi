<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Requests;

/**
 * Open an in-memory stream for example debug output.
 *
 * @param string $path
 * @param string $mode
 *
 * @return resource|false
 */
function fopen(string $path, string $mode)
{
    return \fopen('php://temp', $mode);
}
