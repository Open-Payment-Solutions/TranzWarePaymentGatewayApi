<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Transports;

use OpenPaymentSolutions\TranzWarePaymentGateway\Requests\HTTPClientResult;
use OpenPaymentSolutions\TranzWarePaymentGateway\Requests\HTTPClientResultInterface;

use function OpenPaymentSolutions\TranzWarePaymentGateway\getUserAgent;

class HTTPTransport implements TransportInterface
{
    protected const SCHEME = 'http';
    protected string $userAgent;

    /**
     * Configure an XML POST to an HTTP endpoint.
     *
     * @param string $url
     * @param string|null $body
     * @param string|null $userAgent
     */
    public function __construct(
        protected string $url,
        protected ?string $body = null,
        ?string $userAgent = null
    ) {
        if (strtolower((string)parse_url($url, PHP_URL_SCHEME)) !== static::SCHEME || !parse_url($url, PHP_URL_HOST)) {
            throw new \InvalidArgumentException('Expected a valid ' . static::SCHEME . ' URL.');
        }
        $this->userAgent = getUserAgent($userAgent);
    }

    /**
     * Send XML using PHP streams and preserve HTTP error response bodies.
     *
     * @return HTTPClientResultInterface
     */
    public function execute(): HTTPClientResultInterface
    {
        $started = hrtime(true);
        $stream = null;
        $output = false;
        $error = null;
        $metadata = [];
        $headers = [];
        set_error_handler(static function (int $severity, string $message) use (&$error): bool {
            $error = $message;
            return true;
        }, E_WARNING);
        try {
            $stream = fopen($this->url, 'rb', false, stream_context_create($this->contextOptions()));
            if (is_resource($stream)) {
                $output = stream_get_contents($stream);
                $metadata = stream_get_meta_data($stream);
                $headers = $metadata['wrapper_data'] ?? [];
            }
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
            restore_error_handler();
        }

        $status = 0;
        $fields = [];
        foreach ($headers as $header) {
            if (preg_match('/^HTTP\/\S+\s+(\d{3})(?:\s|$)/i', $header, $matches)) {
                $status = (int)$matches[1];
                $fields = [];
            } elseif (str_contains($header, ':')) {
                [$name, $value] = explode(':', $header, 2);
                $fields[strtolower(trim($name))] = trim($value);
            }
        }

        $timedOut = $metadata['timed_out'] ?? false;
        if ($timedOut) {
            $error = 'HTTP response timed out.';
        } elseif (
            is_string($output) && !in_array($status, [204, 304], true)
            && isset($fields['content-length']) && !isset($fields['transfer-encoding'])
            && (string)strlen($output) !== (ltrim($fields['content-length'], '0') ?: '0')
        ) {
            $error = 'Incomplete HTTP response body.';
        }
        if ($error !== null || $status === 0) {
            $output = false;
        }

        return new HTTPClientResult($output, [
            'http_code' => $status,
            'url' => $this->url,
            'content_type' => $fields['content-type'] ?? null,
            'total_time' => (hrtime(true) - $started) / 1e9,
            'response_headers' => $headers,
            'timed_out' => $timedOut,
            'error' => $error,
        ]);
    }

    /**
     * Configure the built-in HTTP wrapper without following redirects.
     *
     * @return array
     */
    protected function contextOptions(): array
    {
        return ['http' => [
            'method' => 'POST',
            'header' => [
                'Content-Type: application/xml',
                'User-Agent: ' . $this->userAgent,
                'Content-Length: ' . strlen($this->body ?? ''),
                'Connection: close',
            ],
            'content' => $this->body ?? '',
            'protocol_version' => 1.1,
            'timeout' => 10.0,
            'ignore_errors' => true,
            'follow_location' => 0,
        ]];
    }
}
