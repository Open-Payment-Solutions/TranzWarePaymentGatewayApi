<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Data;

class OrderData
{
    /**
     * Store parsed gateway fields and any unparsed data.
     *
     * @param string|null $url
     * @param string|null $orderId
     * @param string|null $sessionId
     * @param string|null $paymentUrl
     * @param array $__unparsed
     */
    public function __construct(
        public readonly ?string $url = null,
        public readonly ?string $orderId = null,
        public readonly ?string $sessionId = null,
        public readonly ?string $paymentUrl = null,
        // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore -- Required public field name.
        public readonly array $__unparsed = []
    ) {
    }
}
