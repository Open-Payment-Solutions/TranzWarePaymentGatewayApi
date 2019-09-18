<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway;

class OrderTypes
{
    const PURCHASE = 'Purchase';
    const PRE_AUTH = 'PreAuth';

    public static function sanitizeValue($value)
    {
        $value = preg_replace("/[^A-Za-z_]/", '', $value);
        $value = trim($value, '_');
        if (!$value) return '';

        $isSnakeCase = strpos($value, '_') > 0;
        if ($isSnakeCase) {
            return
                implode('',
                    array_map('ucwords',
                        array_map('strtolower',
                            explode('_', $value)
                        )
                    )
                );
        }

        $words = implode('_', preg_split('/(?=[A-Z])/', $value));
        return self::sanitizeValue($words);
    }

    public static function isValid($orderType)
    {
        $allowedTypes = [self::PURCHASE, self::PRE_AUTH];
        return in_array($orderType, $allowedTypes);
    }

    public static function fromString($orderType)
    {
        $orderType = self::sanitizeValue($orderType);
        return self::isValid($orderType) ? $orderType : self::PURCHASE;
    }
}