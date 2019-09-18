<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway;

use \OpenPaymentSolutions\TranzWarePaymentGateway\Handlers\TranzWarePaymentGatewayOrderCallbackHandler;

/**
 * Class TranzWarePaymentGatewayHandlerFactory
 * @package OpenPaymentSolutions\TranzWarePaymentGateway
 */
class TranzWarePaymentGatewayHandlerFactory implements TranzWarePaymentGatewayHandlerFactoryInterface
{
    /**
     * @return Handlers\TranzWarePaymentGatewayOrderCallbackHandler
     */
    final public function createOrderCallbackHandler()
    {
        return new TranzWarePaymentGatewayOrderCallbackHandler();
    }
}