<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Requests;

/**
 * Class HTTPClientResult
 *
 * @package OpenPaymentSolutions\TranzWarePaymentGateway\Requests
 */
class HTTPClientResult implements HTTPClientResultInterface
{
    private $info;
    private $output;

    /**
     * HTTPClientResult constructor.
     *
     * @param mixed $output
     * @param mixed $info
     */
    public function __construct($output, $info)
    {
        $this->info = $info;
        $this->output = $output;
    }

    /**
     * @return mixed
     */
    final public function getOutput()
    {
        return $this->output;
    }

    /**
     * @return mixed
     */
    final public function getInfo()
    {
        return $this->info;
    }
}