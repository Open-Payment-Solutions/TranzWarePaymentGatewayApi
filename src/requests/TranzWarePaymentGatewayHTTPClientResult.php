<?php
namespace OpenPaymentSolutions\TranzWarePaymentGateway\Requests;

/**
 * Class TranzWarePaymentGatewayHTTPClientResult
 * @package OpenPaymentSolutions\TranzWarePaymentGateway\Requests
 */
class TranzWarePaymentGatewayHTTPClientResult implements TranzWarePaymentGatewayHTTPClientResultInterface
{
    private $info;
    private $output;

    /**
     * TranzWarePaymentGatewayHTTPClientResult constructor.
     *
     * @param mixed $output
     * @param mixed $info
     */
    public function __construct
    (
        $output,
        $info
    )
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