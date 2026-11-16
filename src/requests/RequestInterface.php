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
     * Override the User-Agent or reset it to the package default with null.
     *
     * @param string|null $userAgent
     *
     * @return void
     */
    public function setUserAgent(?string $userAgent): void;

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
        ?bool $strictSSL = false,
    ): void;

    /**
     * Enable/Disable strict SSL mode.
     * Controls certificate and peer name verification on TLS streams.
     *
     * @param bool $enable
     *
     * @return void
     */
    public function setStrictSSL(bool $enable = true): void;
}
