<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Data;

class OrderStatusData
{
    /**
     * Store parsed gateway fields and any unparsed data.
     *
     * @param string|null $orderId
     * @param string|null $orderStatus
     * @param array $__unparsed
     */
    public function __construct(
        public readonly ?string $orderId = null,
        public readonly ?string $orderStatus = null,
        // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore -- Required public field name.
        public readonly array $__unparsed = []
    ) {
    }
}
