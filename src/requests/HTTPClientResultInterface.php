<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Requests;

/**
 * Interface HTTPClientResultInterface
 *
 * @package OpenPaymentSolutions\TranzWarePaymentGateway\Requests
 */
interface HTTPClientResultInterface
{
    public function __construct(string|false $output, array $info);

    /**
     * Returns request info (headers, status and etc)
     *
     * @return mixed
     */
    public function getInfo(): array;

    /**
     * Returns raw http output
     *
     * @return mixed
     */
    public function getOutput(): string|false;
}
