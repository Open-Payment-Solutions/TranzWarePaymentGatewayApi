<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway;

use OpenPaymentSolutions\TranzWarePaymentGateway\Requests\RequestInterface;

/**
 * Interface RequestFactoryInterface
 *
 * @package OpenPaymentSolutions\TranzWarePaymentGateway
 */
interface RequestFactoryInterface
{
    /**
     * RequestFactoryInterface constructor.
     *
     * @param string $GATEWAY_URL
     * @param string $MERCHANT_ID
     * @param string $ON_ORDER_APPROVED_URL
     * @param string $ON_ORDER_DECLINED_URL
     * @param string $ON_ORDER_CANCELED_URL
     * @param string $LANG (optional)
     */
    public function __construct(
        string $GATEWAY_URL, string $MERCHANT_ID,
        string $ON_ORDER_APPROVED_URL, string $ON_ORDER_DECLINED_URL, string $ON_ORDER_CANCELED_URL,
        string $LANG = 'EN'
    );

    /**
     * Sets verbose mode in requests and file to output
     *
     * @param  string $path_to_file
     * @return void
     */
    public function setDebugFile(string $path_to_file): self;

    /**
     * @param float                                              $amount
     * @param CurrencyCodes|string                                             $currency
     * @param string                                             $description
     * @param OrderTypes|string $orderType
     *
     * @return RequestInterface
     */
    public function createOrderRequest(float $amount, CurrencyCodes|string $currency, string $description = '', OrderTypes|string $orderType = OrderTypes::PURCHASE): RequestInterface;

    /**
     * @param string|null $orderId
     * @param string|null $sessionId
     *
     * @return RequestInterface
     * @throws Exceptions\InvalidOrderId
     * @throws Exceptions\InvalidSessionId
     */
    public function createOrderStatusRequest(?string $orderId, ?string $sessionId): RequestInterface;
}
