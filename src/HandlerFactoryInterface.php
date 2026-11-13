<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway;

use \OpenPaymentSolutions\TranzWarePaymentGateway\Handlers\HandlerInterface;

/**
 * Interface HandlerFactoryInterface
 *
 * @package OpenPaymentSolutions\TranzWarePaymentGateway
 */
interface HandlerFactoryInterface
{
    /**
     * Returns a new instance of HandlerInterface
     * that will handle callbacks after order creation
     *
     * @return HandlerInterface
     */
    public function createOrderCallbackHandler();
}