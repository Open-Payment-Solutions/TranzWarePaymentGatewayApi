<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway;

use \OpenPaymentSolutions\TranzWarePaymentGateway\Handlers\OrderCallbackHandler;

/**
 * Class HandlerFactory
 *
 * @package OpenPaymentSolutions\TranzWarePaymentGateway
 */
class HandlerFactory implements HandlerFactoryInterface
{
    /**
     * @return Handlers\OrderCallbackHandler
     */
    final public function createOrderCallbackHandler()
    {
        return new OrderCallbackHandler();
    }
}