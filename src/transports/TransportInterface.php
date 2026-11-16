<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Transports;

use OpenPaymentSolutions\TranzWarePaymentGateway\Requests\HTTPClientResultInterface;

interface TransportInterface
{
    /**
     * Execute the configured XML POST request.
     *
     * @return HTTPClientResultInterface
     */
    public function execute(): HTTPClientResultInterface;
}
