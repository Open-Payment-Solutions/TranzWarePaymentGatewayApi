<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Data;

class OrderCallbackData
{
    /**
     * Store parsed gateway fields and any unparsed data.
     *
     * @param string $dateTime
     * @param string $orderId
     * @param string $amount
     * @param string $currency
     * @param string $orderStatus
     * @param string $xmlMessage
     * @param array $__unparsed
     */
    public function __construct(
        public readonly string $dateTime,
        public readonly string $orderId,
        public readonly string $amount,
        public readonly string $currency,
        public readonly string $orderStatus,
        public readonly string $xmlMessage,
        // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore -- Required public field name.
        public readonly array $__unparsed = []
    ) {
    }
}
