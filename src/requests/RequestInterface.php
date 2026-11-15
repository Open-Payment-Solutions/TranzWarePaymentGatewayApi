<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Requests;

/**
 * Interface RequestInterface
 *
 * @package OpenPaymentSolutions\TranzWarePaymentGateway\Requests
 */
interface RequestInterface
{
    /**
     * Executes request and returns instance of
     * class that implements RequestResultInterface
     *
     * @return RequestResultInterface
     */
    public function execute(): RequestResultInterface;

    /**
     * Sets ssl certificate file path required in request
     *
     * @param string|null $cert
     * @param string|null $key
     * @param string|null $keyPass   (optional)
     * @param bool|null $strictSSL (optional)
     *
     * @return void
     */
    public function setSslCertificate(
        ?string $cert,
        ?string $key,
        ?string $keyPass = '',
        ?bool $strictSSL = true,
    ): void;

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
    public function setStrictSSL(bool $enable = true): void;
}
