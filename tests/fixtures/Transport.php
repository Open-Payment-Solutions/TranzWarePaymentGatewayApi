<?php

namespace tests\fixtures;

final class Transport
{
    public static bool $enabled = false;
    public static array $options = [];
    public static string|false $output = '';
    public static array $info = [];
}

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Requests;

use tests\fixtures\Transport;

/**
 * Record cURL options when transport simulation is enabled.
 *
 * @param \CurlHandle $handle
 * @param int $option
 * @param mixed $value
 *
 * @return bool
 */
function curl_setopt(\CurlHandle $handle, int $option, mixed $value): bool
{
    if (!Transport::$enabled) {
        return \curl_setopt($handle, $option, $value);
    }
    Transport::$options[$option] = $value;
    return true;
}

/**
 * Return the simulated response or execute the real cURL request.
 *
 * @param \CurlHandle $handle
 *
 * @return string|bool
 */
function curl_exec(\CurlHandle $handle): string|bool
{
    return Transport::$enabled ? Transport::$output : \curl_exec($handle);
}

/**
 * Return simulated or real cURL transport information.
 *
 * @param \CurlHandle $handle
 *
 * @return array
 */
function curl_getinfo(\CurlHandle $handle): array
{
    return Transport::$enabled ? Transport::$info : \curl_getinfo($handle);
}
