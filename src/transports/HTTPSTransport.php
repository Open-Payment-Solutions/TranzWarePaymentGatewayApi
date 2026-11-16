<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Transports;

class HTTPSTransport extends HTTPTransport
{
    protected const SCHEME = 'https';

    /**
     * Configure an HTTPS POST with optional client certificate authentication.
     *
     * @param string $url
     * @param string|null $body
     * @param array|null $ssl
     * @param bool $strictSSL
     * @param string|null $userAgent
     */
    public function __construct(
        string $url,
        ?string $body = null,
        private ?array $ssl = null,
        private bool $strictSSL = false,
        ?string $userAgent = null
    ) {
        parent::__construct($url, $body, $userAgent);
    }

    /**
     * Apply certificate, private key, and CA settings to the TLS stream.
     *
     * @return array
     */
    protected function contextOptions(): array
    {
        $options = parent::contextOptions();
        $options['ssl'] = [
            'verify_peer' => $this->strictSSL,
            'verify_peer_name' => $this->strictSSL,
            'allow_self_signed' => !$this->strictSSL,
        ];
        foreach (['cert' => 'local_cert', 'key' => 'local_pk', 'keyPass' => 'passphrase'] as $key => $option) {
            if (isset($this->ssl[$key])) {
                $options['ssl'][$option] = $this->ssl[$key];
            }
        }
        foreach (['path' => 'capath', 'file' => 'cafile'] as $key => $option) {
            if (!empty($this->ssl['ca'][$key])) {
                $options['ssl'][$option] = $this->ssl['ca'][$key];
            }
        }
        return $options;
    }
}
