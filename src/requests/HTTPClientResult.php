<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Requests;

/**
 * Class HTTPClientResult
 *
 * @package OpenPaymentSolutions\TranzWarePaymentGateway\Requests
 */
class HTTPClientResult implements HTTPClientResultInterface
{
    private array $info;
    private string|false $output;

    /**
     * HTTPClientResult constructor.
     *
     * @param mixed $output
     * @param mixed $info
     */
    public function __construct(string|false $output, array $info)
    {
        $this->info = $info;
        $this->output = $output;
    }

    /**
     * @return mixed
     */
    final public function getOutput(): string|false
    {
        return $this->output;
    }

    /**
     * @return mixed
     */
    final public function getInfo(): array
    {
        return $this->info;
    }
}
