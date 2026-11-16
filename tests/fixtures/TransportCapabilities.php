<?php

namespace tests\fixtures;

final class TransportCapabilities
{
    public static array $extensions = [];
    public static array $functions = [];
    public static array $settings = [];
    public static ?array $wrappers = null;
    public static ?array $transports = null;
    public static ?array $curlProtocols = null;
}

namespace OpenPaymentSolutions\TranzWarePaymentGateway;

use tests\fixtures\Transport;
use tests\fixtures\TransportCapabilities;

/**
 * Simulate runtime extensions without changing the test process configuration.
 *
 * @param string $name
 *
 * @return bool
 */
function extension_loaded(string $name): bool
{
    return TransportCapabilities::$extensions[$name]
        ?? ($name === 'curl' && Transport::$enabled ? false : \extension_loaded($name));
}

/**
 * Simulate functions disabled by PHP configuration.
 *
 * @param string $name
 *
 * @return bool
 */
function function_exists(string $name): bool
{
    return TransportCapabilities::$functions[$name] ?? \function_exists($name);
}

/**
 * Simulate runtime settings for transport availability tests.
 *
 * @param string $name
 *
 * @return string|false
 */
function ini_get(string $name): string|false
{
    return TransportCapabilities::$settings[$name] ?? \ini_get($name);
}

/**
 * Provide simulated or actual URL wrappers.
 *
 * @return array
 */
function stream_get_wrappers(): array
{
    return TransportCapabilities::$wrappers ?? \stream_get_wrappers();
}

/**
 * Provide simulated or actual network stream transports.
 *
 * @return array
 */
function stream_get_transports(): array
{
    return TransportCapabilities::$transports ?? \stream_get_transports();
}

/**
 * Provide cURL protocol support for selection tests without making requests.
 *
 * @return array
 */
function curl_version(): array
{
    return TransportCapabilities::$curlProtocols !== null
        ? ['protocols' => TransportCapabilities::$curlProtocols] : \curl_version();
}
