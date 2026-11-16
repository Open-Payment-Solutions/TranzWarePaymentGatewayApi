<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Requests;

use function OpenPaymentSolutions\TranzWarePaymentGateway\getUserAgent;

trait RequestSettings
{
    private bool $strictSSL = false;
    private ?string $sslKey = null;
    private ?string $sslKeyPass = '';
    private ?string $sslCertificate = null;
    private ?string $userAgent = null;

    /**
     * Override the User-Agent or reset it to the package default with null.
     *
     * @param string|null $userAgent
     *
     * @return void
     */
    final public function setUserAgent(?string $userAgent): void
    {
        $this->userAgent = $userAgent === null ? null : getUserAgent($userAgent);
    }

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
    final public function setSslCertificate(
        ?string $cert,
        ?string $key,
        ?string $keyPass = '',
        ?bool $strictSSL = false,
    ): void {
        $this->sslKey = $key;
        $this->sslKeyPass = $keyPass;
        $this->sslCertificate = $cert;
        $this->setStrictSSL($strictSSL ?? false);
    }

    /**
     * Enable/Disable strict SSL mode.
     * Controls certificate and peer name verification on TLS streams.
     *
     * @param bool $enable
     *
     * @return void
     */
    final public function setStrictSSL(bool $enable = true): void
    {
        $this->strictSSL = $enable;
    }

    final public function enableSSLVerification(): void
    {
        $this->setStrictSSL(true);
    }

    final public function disableSSLVerification(): void
    {
        $this->setStrictSSL(false);
    }
}
