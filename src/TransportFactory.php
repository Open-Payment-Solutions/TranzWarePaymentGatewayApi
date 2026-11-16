<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway;

use OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions\TransportNotAvailable;
use OpenPaymentSolutions\TranzWarePaymentGateway\Transports\CurlTransport;
use OpenPaymentSolutions\TranzWarePaymentGateway\Transports\HTTPTransport;
use OpenPaymentSolutions\TranzWarePaymentGateway\Transports\HTTPSTransport;
use OpenPaymentSolutions\TranzWarePaymentGateway\Transports\TransportInterface;

class TransportFactory
{
    /**
     * Prefer cURL, otherwise select an available PHP stream transport.
     *
     * @param string $url
     * @param string|null $body
     * @param array|null $ssl
     * @param bool $strictSSL
     * @param string|null $userAgent
     *
     * @return TransportInterface
     * @throws TransportNotAvailable
     */
    final public function create(
        string $url,
        ?string $body = null,
        ?array $ssl = null,
        bool $strictSSL = false,
        ?string $userAgent = null
    ): TransportInterface {
        $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
        if (!in_array($scheme, ['http', 'https'], true) || !parse_url($url, PHP_URL_HOST)) {
            throw new \InvalidArgumentException('Only valid HTTP and HTTPS URLs are supported.');
        }

        $curlReasons = $this->curlUnavailableReasons($scheme);
        if ($curlReasons === []) {
            return new CurlTransport($url, $body, $ssl, $strictSSL, $userAgent);
        }

        $streamReasons = $this->streamUnavailableReasons($scheme);
        if ($streamReasons === []) {
            return $scheme === 'https'
                ? new HTTPSTransport($url, $body, $ssl, $strictSSL, $userAgent)
                : new HTTPTransport($url, $body, $userAgent);
        }

        throw new TransportNotAvailable(
            'No transport is available for ' . strtoupper($scheme) . '. '
            . 'cURL: ' . implode('; ', $curlReasons) . '. '
            . 'Streams: ' . implode('; ', $streamReasons) . '.'
        );
    }

    /**
     * Explain missing cURL functions or protocol support.
     *
     * @param string $scheme
     *
     * @return array
     */
    private function curlUnavailableReasons(string $scheme): array
    {
        if (!extension_loaded('curl')) {
            return ['extension is not loaded'];
        }
        $missing = $this->missingFunctions([
            'curl_init', 'curl_setopt_array', 'curl_exec', 'curl_getinfo', 'curl_error', 'curl_version',
        ]);
        if ($missing !== []) {
            return ['functions are unavailable or disabled: ' . implode(', ', $missing)];
        }
        if (!in_array($scheme, curl_version()['protocols'] ?? [], true)) {
            return [strtoupper($scheme) . ' protocol is not supported by this cURL build'];
        }
        return [];
    }

    /**
     * Explain disabled stream functions, URL wrappers, or TLS support.
     *
     * @param string $scheme
     *
     * @return array
     */
    private function streamUnavailableReasons(string $scheme): array
    {
        $reasons = [];
        $missing = $this->missingFunctions([
            'fopen', 'fclose', 'stream_context_create', 'stream_get_contents', 'stream_get_meta_data',
            'stream_get_wrappers', 'stream_get_transports', 'set_error_handler', 'restore_error_handler', 'hrtime',
        ]);
        if ($missing !== []) {
            $reasons[] = 'functions are unavailable or disabled: ' . implode(', ', $missing);
        }
        if (!in_array(strtolower((string)ini_get('allow_url_fopen')), ['1', 'on', 'true', 'yes'], true)) {
            $reasons[] = 'allow_url_fopen is disabled';
        }
        if (function_exists('stream_get_wrappers') && !in_array($scheme, stream_get_wrappers(), true)) {
            $reasons[] = $scheme . ' URL wrapper is not registered';
        }
        if (function_exists('stream_get_transports')) {
            if (!in_array('tcp', stream_get_transports(), true)) {
                $reasons[] = 'TCP stream transport is not registered';
            }
            if ($scheme === 'https' && !in_array('ssl', stream_get_transports(), true)) {
                $reasons[] = 'SSL stream transport is not registered';
            }
        }
        if ($scheme === 'https' && !extension_loaded('openssl')) {
            $reasons[] = 'OpenSSL extension is not loaded';
        }
        return $reasons;
    }

    /**
     * Find functions disabled by PHP configuration or unavailable in this build.
     *
     * @param array $functions
     *
     * @return array
     */
    private function missingFunctions(array $functions): array
    {
        return array_values(array_filter($functions, static fn (string $name): bool => !function_exists($name)));
    }
}
