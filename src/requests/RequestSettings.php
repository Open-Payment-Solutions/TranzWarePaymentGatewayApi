<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Requests;

trait RequestSettings
{
    private bool $strictSSL = true;
    private ?string $sslKey = null;
    private ?string $sslKeyPass = '';
    private ?string $sslCertificate = null;

    /**
     * Sets ssl certificate file path required in request
     *
     * @param string $cert
     * @param string $key
     * @param string $keyPass   (optional)
     * @param bool   $strictSSL (optional)
     *
     * @return void
     */
    final public function setSslCertificate(
        ?string $cert,
        ?string $key,
        ?string $keyPass = '',
        ?bool $strictSSL = true,
    ): void {
        $this->sslKey = $key;
        $this->sslKeyPass = $keyPass;
        $this->sslCertificate = $cert;
        $this->setStrictSSL($strictSSL ?? true);
    }

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
