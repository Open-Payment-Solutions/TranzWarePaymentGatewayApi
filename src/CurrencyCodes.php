<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway;

enum CurrencyCodes: string
{
    case USD = '840';
    case EUR = '978';
    case AZN = '944';
    case RUB = '643';
    case SDG = '938';

    /**
     * Normalize a currency string.
     *
     * @param string $value
     *
     * @return string
     */
    public static function sanitizeValue(string $value): string
    {
        return match (strtoupper(trim($value))) {
            'USD' => self::USD->value,
            'EUR' => self::EUR->value,
            'AZN' => self::AZN->value,
            'RUB' => self::RUB->value,
            'SDG' => self::SDG->value,
            default => trim($value),
        };
    }

    /**
     * Check whether the currency is supported.
     *
     * @param self|string $currency
     *
     * @return bool
     */
    public static function isValid(self|string $currency): bool
    {
        return $currency instanceof self || self::tryFrom(self::sanitizeValue($currency)) !== null;
    }

    /**
     * Parse a currency into its enum case.
     *
     * @param self|string $currency
     *
     * @return self
     */
    public static function fromString(self|string $currency): self
    {
        return $currency instanceof self ? $currency : self::from(self::sanitizeValue($currency));
    }
}
