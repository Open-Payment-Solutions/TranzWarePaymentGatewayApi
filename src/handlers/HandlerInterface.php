<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Handlers;

use OpenPaymentSolutions\TranzWarePaymentGateway\Data\OrderCallbackData;

/**
 * Interface HandlerInterface
 *
 * @package OpenPaymentSolutions\TranzWarePaymentGateway\Handlers
 */
interface HandlerInterface
{
    /**
     * Parse callback fields and retain unparsed gateway data.
     *
     * @return OrderCallbackData
     */
    public function handle(): OrderCallbackData;
}
