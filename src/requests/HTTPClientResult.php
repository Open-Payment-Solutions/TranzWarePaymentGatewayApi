<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Requests;

use OpenPaymentSolutions\TranzWarePaymentGateway\Data\HTTPInfo;

/**
 * Class HTTPClientResult
 *
 * @package OpenPaymentSolutions\TranzWarePaymentGateway\Requests
 */
class HTTPClientResult implements HTTPClientResultInterface
{
    private HTTPInfo $info;
    private string|false $output;

    /**
     * HTTPClientResult constructor.
     *
     * @param string|false $output
     * @param HTTPInfo|array $info
     */
    public function __construct(string|false $output, HTTPInfo|array $info)
    {
        $this->info = $info instanceof HTTPInfo ? $info : new HTTPInfo($info);
        $this->output = $output;
    }

    /**
     * Return the raw HTTP response body.
     *
     * @return string|false
     */
    final public function getOutput(): string|false
    {
        return $this->output;
    }

    /**
     * Return HTTP transport information.
     *
     * @return HTTPInfo
     */
    final public function getInfo(): HTTPInfo
    {
        return $this->info;
    }
}
