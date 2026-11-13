<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Requests;

/**
 * Interface HTTPClientResultInterface
 *
 * @package OpenPaymentSolutions\TranzWarePaymentGateway\Requests
 */
interface HTTPClientResultInterface
{
    public function __construct($output, $info);

    /**
     * Returns request info (headers, status and etc)
     *
     * @return mixed
     */
    public function getInfo();

    /**
     * Returns raw http output
     *
     * @return mixed
     */
    public function getOutput();
}