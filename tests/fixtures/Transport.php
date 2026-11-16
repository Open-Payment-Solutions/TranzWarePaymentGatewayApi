<?php

namespace tests\fixtures;

final class Transport
{
    public static bool $enabled = false;
    public static array $options = [];
    public static string $url = '';
    public static mixed $stream = null;
    public static string|false $output = '';
    public static array $info = [];
}

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Transports;

use tests\fixtures\Transport;

/**
 * Record stream options and provide an in-memory response during unit tests.
 *
 * @param string $filename
 * @param string $mode
 * @param bool $useIncludePath
 * @param resource|null $context
 *
 * @return resource|false
 */
function fopen(string $filename, string $mode, bool $useIncludePath = false, $context = null)
{
    if (!Transport::$enabled) {
        return \fopen($filename, $mode, $useIncludePath, $context);
    }
    Transport::$url = $filename;
    Transport::$options = stream_context_get_options($context);
    if (Transport::$output === false) {
        Transport::$stream = false;
        return false;
    }
    $stream = \fopen('php://temp', 'w+');
    fwrite($stream, Transport::$output);
    rewind($stream);
    Transport::$stream = $stream;
    return $stream;
}

/**
 * Provide simulated HTTP response headers and timeout state.
 *
 * @param resource $stream
 *
 * @return array
 */
function stream_get_meta_data($stream): array
{
    if (!Transport::$enabled) {
        return \stream_get_meta_data($stream);
    }
    $headers = ['HTTP/1.1 ' . (Transport::$info['http_code'] ?? 200) . ' Test'];
    if (isset(Transport::$info['content_type'])) {
        $headers[] = 'Content-Type: ' . Transport::$info['content_type'];
    }
    return [
        'wrapper_data' => Transport::$info['response_headers'] ?? $headers,
        'timed_out' => Transport::$info['timed_out'] ?? false,
    ];
}
