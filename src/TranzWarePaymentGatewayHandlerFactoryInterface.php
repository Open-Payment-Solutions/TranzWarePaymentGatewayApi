<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway;

use \OpenPaymentSolutions\TranzWarePaymentGateway\Handlers\TranzWarePaymentGatewayHandlerInterface;

/**
 * Interface TranzWarePaymentGatewayHandlerFactoryInterface
 * @package OpenPaymentSolutions\TranzWarePaymentGateway
 */
interface TranzWarePaymentGatewayHandlerFactoryInterface
{
    /**
     * Returns a new instance of TranzWarePaymentGatewayHandlerInterface
     * that will handle callbacks after order creation
     *
     * @return TranzWarePaymentGatewayHandlerInterface
     */
    public function createOrderCallbackHandler();
}