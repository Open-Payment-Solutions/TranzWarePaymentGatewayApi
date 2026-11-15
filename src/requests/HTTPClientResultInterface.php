<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Requests;

use OpenPaymentSolutions\TranzWarePaymentGateway\Data\HTTPInfo;

/**
 * Interface HTTPClientResultInterface
 *
 * @package OpenPaymentSolutions\TranzWarePaymentGateway\Requests
 */
interface HTTPClientResultInterface
{
    /**
     * Initialize the raw HTTP response and transport information.
     *
     * @param string|false $output
     * @param HTTPInfo|array $info
     */
    public function __construct(string|false $output, HTTPInfo|array $info);

    /**
     * Returns request info (headers, status and etc)
     *
     * @return HTTPInfo
     */
    public function getInfo(): HTTPInfo;

    /**
     * Returns raw http output
     *
     * @return string|false
     */
    public function getOutput(): string|false;
}
