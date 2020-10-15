<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway;

use \Jawira\CaseConverter\CaseConverterException;
use \Jawira\CaseConverter\Convert;

class OrderTypes
{
    const PURCHASE = 'Purchase';
    const PRE_AUTH = 'PreAuth';

    public static function sanitizeValue($value)
    {
        if (!preg_match('/^[A-Za-z_]+?/', $value)) {
            return '';
        }
        try {
            return (new Convert($value))->toPascal();
        } catch (CaseConverterException $e) {}
        return '';
    }

    public static function isValid($orderType)
    {
        $allowedTypes = [
            self::PURCHASE,
            self::PRE_AUTH
        ];
        return in_array($orderType, $allowedTypes);
    }

    public static function fromString($orderType)
    {
        $orderType = self::sanitizeValue($orderType);
        return self::isValid($orderType) ? $orderType : self::PURCHASE;
    }
}