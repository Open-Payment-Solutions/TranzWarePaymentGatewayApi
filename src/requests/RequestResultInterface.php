<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Requests;

use OpenPaymentSolutions\TranzWarePaymentGateway\Data\OrderData;
use OpenPaymentSolutions\TranzWarePaymentGateway\Data\OrderStatusData;

/**
 * Interface RequestResultInterface
 *
 * @package OpenPaymentSolutions\TranzWarePaymentGateway\Requests
 */
interface RequestResultInterface
{
    /**
     * RequestResultInterface constructor.
     *
     * @param HTTPClientResultInterface $HTTPClientResult
     */
    public function __construct(HTTPClientResultInterface $HTTPClientResult);

    /**
     * Returns http status
     *
     * @return integer
     */
    public function getHttpStatus(): int;

    /**
     * Returns raw response body
     *
     * @return string|false
     */
    public function getResponseBody(): string|false;

    /**
     * Returns true if request is successful
     *
     * @return bool
     */
    public function success(): bool;

    /**
     * Returns request processing status
     *
     * @return string|null
     */
    public function getStatus(): ?string;

    /**
     * Returns usable structure from processing of response body
     *
     * @return OrderData|OrderStatusData
     */
    public function getData(): OrderData|OrderStatusData;
}
