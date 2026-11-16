<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway;

/**
 * Convert a UTF-8 string to PascalCase; invalid encoding produces an empty string.
 *
 * @param string $value
 *
 * @return string
 */
function toPascalCase(string $value): string
{
    $pattern = match (true) {
        str_contains($value, '_') => '/_+/u',
        str_contains($value, '-') => '/-+/u',
        str_contains($value, ' ') => '/ +/u',
        str_contains($value, '.') => '/\.+/u',
        preg_match('/^\p{Lu}+$/u', $value) === 1 => '/_+/u',
        default => '/(?=\p{Lu})/u',
    };
    $words = preg_split($pattern, $value, -1, PREG_SPLIT_NO_EMPTY);

    if ($words === false) {
        return '';
    }

    return implode('', array_map(
        static fn (string $word): string => mb_convert_case($word, MB_CASE_TITLE, 'UTF-8'),
        $words
    ));
}

/**
 * Resolve the package User-Agent or validate an optional application override.
 *
 * @param string|null $value
 *
 * @return string
 */
function getUserAgent(?string $value = null): string
{
    if ($value !== null) {
        if (trim($value) === '' || strpbrk($value, "\r\n\0") !== false) {
            throw new \InvalidArgumentException('User-Agent must be nonempty and contain no CR, LF, or NUL bytes.');
        }
        return $value;
    }

    static $default;
    return $default ??= 'OpenPaymentSolutions/TranzWarePaymentGateway/v'
        . trim(file_get_contents(__DIR__ . '/../VERSION'));
}
