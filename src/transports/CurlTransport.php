<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Transports;

use OpenPaymentSolutions\TranzWarePaymentGateway\Requests\HTTPClientResult;
use OpenPaymentSolutions\TranzWarePaymentGateway\Requests\HTTPClientResultInterface;

use function OpenPaymentSolutions\TranzWarePaymentGateway\getUserAgent;

class CurlTransport implements TransportInterface
{
    private string $userAgent;

    /**
     * Configure an HTTP or HTTPS XML POST using cURL.
     *
     * @param string $url
     * @param string|null $body
     * @param array|null $ssl
     * @param bool $strictSSL
     * @param string|null $userAgent
     */
    public function __construct(
        private string $url,
        private ?string $body = null,
        private ?array $ssl = null,
        private bool $strictSSL = false,
        ?string $userAgent = null
    ) {
        if (
            !in_array(strtolower((string)parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)
            || !parse_url($url, PHP_URL_HOST)
        ) {
            throw new \InvalidArgumentException('Only valid HTTP and HTTPS URLs are supported.');
        }
        $this->userAgent = getUserAgent($userAgent);
    }

    /**
     * Send XML and retain cURL diagnostics without following redirects.
     *
     * @return HTTPClientResultInterface
     */
    public function execute(): HTTPClientResultInterface
    {
        $handle = curl_init($this->url);
        if ($handle === false) {
            return new HTTPClientResult(false, ['url' => $this->url, 'error' => 'Unable to initialize cURL.']);
        }
        try {
            $options = [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $this->body ?? '',
                CURLOPT_USERAGENT => $this->userAgent,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/xml',
                    'Content-Length: ' . strlen($this->body ?? ''),
                    'Connection: close',
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => false,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_FAILONERROR => false,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYHOST => $this->strictSSL ? 2 : 0,
                CURLOPT_SSL_VERIFYPEER => $this->strictSSL,
            ];
            $certificateOptions = ['cert' => CURLOPT_SSLCERT, 'key' => CURLOPT_SSLKEY, 'keyPass' => CURLOPT_KEYPASSWD];
            foreach ($certificateOptions as $key => $option) {
                if (isset($this->ssl[$key])) {
                    $options[$option] = $this->ssl[$key];
                }
            }
            foreach (['path' => CURLOPT_CAPATH, 'file' => CURLOPT_CAINFO] as $key => $option) {
                if (!empty($this->ssl['ca'][$key])) {
                    $options[$option] = $this->ssl['ca'][$key];
                }
            }
            $configured = curl_setopt_array($handle, $options);
            $output = $configured ? curl_exec($handle) : false;
            $info = curl_getinfo($handle);
            $info['error'] = curl_error($handle) ?: ($configured ? null : 'Unable to configure cURL.');
            return new HTTPClientResult($output, $info);
        } finally {
            unset($handle);
        }
    }
}
