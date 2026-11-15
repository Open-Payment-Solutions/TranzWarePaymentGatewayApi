<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway;

use OpenPaymentSolutions\TranzWarePaymentGateway\Handlers\OrderCallbackHandler;

/**
 * Class HandlerFactory
 *
 * @package OpenPaymentSolutions\TranzWarePaymentGateway
 */
class HandlerFactory implements HandlerFactoryInterface
{
    /**
     * Create an order callback handler.
     *
     * @return Handlers\OrderCallbackHandler
     */
    final public function createOrderCallbackHandler(): OrderCallbackHandler
    {
        return new OrderCallbackHandler();
    }
}
