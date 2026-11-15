<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway;

use Jawira\CaseConverter\CaseConverterException;
use Jawira\CaseConverter\Convert;
use OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions\InvalidOrderType;

enum OrderTypes: string
{
    case PURCHASE = 'Purchase';
    case PRE_AUTH = 'PreAuth';

    public static function sanitizeValue(string $value): string
    {
        if (!preg_match('/^[A-Za-z_]+?/', $value)) {
            return '';
        }
        try {
            return (new Convert($value))->toPascal();
        } catch (CaseConverterException) {
            return '';
        }
    }

    public static function isValid(self|string $orderType): bool
    {
        return $orderType instanceof self || self::tryFrom($orderType) !== null;
    }

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
