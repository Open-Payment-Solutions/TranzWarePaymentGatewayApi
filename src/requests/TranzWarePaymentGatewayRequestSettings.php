<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Requests;

trait TranzWarePaymentGatewayRequestSettings
{
    private $strictSSL, $sslKey, $sslKeyPass, $sslCertificate;

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
    final public function setSslCertificate($cert, $key, $keyPass = '', $strictSSL = true)
    {
        $this->sslKey = $key;
        $this->sslKeyPass = $keyPass;
        $this->sslCertificate = $cert;
        $this->setStrictSSL((bool)$strictSSL);
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
    final public function setStrictSSL($enable = true)
    {
        $this->strictSSL = $enable;
    }

    final public function enableSSLVerification()
    {
        $this->setStrictSSL(true);
    }

    final public function disableSSLVerification()
    {
        $this->setStrictSSL(false);
    }
}