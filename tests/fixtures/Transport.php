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

function curl_setopt(\CurlHandle $handle, int $option, mixed $value): bool
{
    if (!Transport::$enabled) {
        return \curl_setopt($handle, $option, $value);
    }
    Transport::$options[$option] = $value;
    return true;
}

function curl_exec(\CurlHandle $handle): string|bool
{
    return Transport::$enabled ? Transport::$output : \curl_exec($handle);
}

function curl_getinfo(\CurlHandle $handle): array
{
    return Transport::$enabled ? Transport::$info : \curl_getinfo($handle);
}
