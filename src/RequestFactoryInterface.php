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
     * Override the User-Agent or reset it to the package default with null.
     *
     * @param string|null $userAgent
     *
     * @return self
     */
    public function setUserAgent(?string $userAgent): self;

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
        string $GATEWAY_URL,
        string $MERCHANT_ID,
        string $ON_ORDER_APPROVED_URL,
        string $ON_ORDER_DECLINED_URL,
        string $ON_ORDER_CANCELED_URL,
        string $LANG = 'EN'
    );

    /**
     * Set the client certificate and SSL verification mode.
     *
     * @param string $certFile
     * @param string $keyFile
     * @param string|null $keyPass
     * @param bool|null $strictSSL
     *
     * @return self
     */
    public function setCertificate(
        string $certFile,
        string $keyFile,
        ?string $keyPass = '',
        ?bool $strictSSL = null,
    ): self;

    /**
     * Enable SSL verification.
     *
     * @return self
     */
    public function enableSSLVerification(): self;

    /**
     * Disable SSL verification.
     *
     * @return self
     */
    public function disableSSLVerification(): self;

    /**
     * Sets verbose mode in requests and file to output
     *
     * @param string $path_to_file
     *
     * @return self
     */
    public function setDebugFile(string $path_to_file): self;

    /**
     * Create an order request.
     *
     * @param float $amount
     * @param CurrencyCodes|string $currency
     * @param string $description
     * @param OrderTypes|string $orderType
     *
     * @return RequestInterface
     */
    public function createOrderRequest(
        float $amount,
        CurrencyCodes|string $currency,
        string $description = '',
        OrderTypes|string $orderType = OrderTypes::PURCHASE,
    ): RequestInterface;

    /**
     * Create a purchase request.
     *
     * @param float $amount
     * @param CurrencyCodes|string $currency
     * @param string $description
     *
     * @return RequestInterface
     */
    public function createOrderPurchaseRequest(
        float $amount,
        CurrencyCodes|string $currency,
        string $description = '',
    ): RequestInterface;

    /**
     * Create a preauthorization request.
     *
     * @param float $amount
     * @param CurrencyCodes|string $currency
     * @param string $description
     *
     * @return RequestInterface
     */
    public function createOrderPreAuthRequest(
        float $amount,
        CurrencyCodes|string $currency,
        string $description = '',
    ): RequestInterface;

    /**
     * Create an order status request.
     *
     * @param string|null $orderId
     * @param string|null $sessionId
     *
     * @return RequestInterface
     * @throws Exceptions\InvalidOrderId
     * @throws Exceptions\InvalidSessionId
     */
    public function createOrderStatusRequest(?string $orderId, ?string $sessionId): RequestInterface;
}
