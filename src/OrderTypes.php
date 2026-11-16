<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway;

use OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions\InvalidOrderType;

enum OrderTypes: string
{
    case PURCHASE = 'Purchase';
    case PRE_AUTH = 'PreAuth';

    /**
     * Normalize a order type string.
     *
     * @param string $value
     *
     * @return string
     */
    public static function sanitizeValue(string $value): string
    {
        if (!preg_match('/^[A-Za-z_]+?/', $value)) {
            return '';
        }

        return toPascalCase($value);
    }

    /**
     * Check whether the order type is supported.
     *
     * @param self|string $orderType
     *
     * @return bool
     */
    public static function isValid(self|string $orderType): bool
    {
        return $orderType instanceof self || self::tryFrom($orderType) !== null;
    }

    /**
     * Parse a order type into its enum case.
     *
     * @param self|string $orderType
     *
     * @return self
     */
    public static function fromString(self|string $orderType): self
    {
        if ($orderType instanceof self) {
            return $orderType;
        }

        return match (self::sanitizeValue($orderType)) {
            self::PRE_AUTH->value => self::PRE_AUTH,
            self::PURCHASE->value => self::PURCHASE,
            default => throw new InvalidOrderType($orderType),
        };
    }
}
