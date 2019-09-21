<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Requests;

/**
 * Interface TranzWarePaymentGatewayRequestInterface
 * @package OpenPaymentSolutions\TranzWarePaymentGateway\Requests
 */
interface TranzWarePaymentGatewayRequestInterface
{
    /**
     * Executes request and returns instance of
     * class that implements TranzWarePaymentGatewayRequestResultInterface
     *
     * @return TranzWarePaymentGatewayRequestResultInterface
     */
    public function execute();

    /**
     * Sets ssl certificate file path required in request
     *
     * @param string $cert
     * @param string $key
     * @param string $keyPass
     * @param bool $strictSSL
     *
     * @return void
     */
    public function setSslCertificate($cert, $key, $keyPass = '', $strictSSL = false);

    /**
     * Enable/Disable strict SSL mode.
     * Modifies following params on request time:
     *   CURLOPT_SSL_VERIFYHOST
     *   CURLOPT_SSL_VERIFYPEER
     *
     * @param bool $enable
     *
     * @return void
     */
    public function setStrictSSL($enable = true);
}