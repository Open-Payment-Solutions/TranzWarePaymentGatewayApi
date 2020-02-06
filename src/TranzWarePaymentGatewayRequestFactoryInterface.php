<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway;

use \OpenPaymentSolutions\TranzWarePaymentGateway\Requests\TranzWarePaymentGatewayRequestInterface;

/**
 * Interface TranzWarePaymentGatewayRequestFactoryInterface
 *
 * @package OpenPaymentSolutions\TranzWarePaymentGateway
 */
interface TranzWarePaymentGatewayRequestFactoryInterface
{
    /**
     * TranzWarePaymentGatewayRequestFactoryInterface constructor.
     *
     * @param string $GATEWAY_URL
     * @param string $MERCHANT_ID
     * @param string $ON_ORDER_APPROVED_URL
     * @param string $ON_ORDER_DECLINED_URL
     * @param string $ON_ORDER_CANCELED_URL
     * @param string $LANG (optional)
     */
    public function __construct(
        $GATEWAY_URL, $MERCHANT_ID,
        $ON_ORDER_APPROVED_URL, $ON_ORDER_DECLINED_URL, $ON_ORDER_CANCELED_URL,
        $LANG = 'EN'
    );

    /**
     * Sets verbose mode in requests and file to output
     *
     * @param  string $path_to_file
     * @return void
     */
    public function setDebugFile($path_to_file);

    /**
     * @param float                                              $amount
     * @param string                                             $currency
     * @param string                                             $description
     * @param string{OrderTypes::PURCHASE, OrderTypes::PRE_AUTH} $orderType
     *
     * @return TranzWarePaymentGatewayRequestInterface
     */
    public function createOrderRequest($amount, $currency, $description = '', $orderType = OrderTypes::PURCHASE);

    /**
     * @param string $orderId
     * @param string $sessionId
     *
     * @return TranzWarePaymentGatewayRequestInterface
     */
    public function createOrderStatusRequest($orderId, $sessionId);
}