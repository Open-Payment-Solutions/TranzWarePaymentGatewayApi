<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Handlers;

use OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions\EmptyXMLMessage;
use OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions\InvalidXMLMessage;

/**
 * Class that handles request from TWPG after approved/canceled/declined order creation on TWPG site
 *
 * Class OrderCallbackHandler
 *
 * @package OpenPaymentSolutions\TranzWarePaymentGateway\Handlers
 */
class OrderCallbackHandler implements HandlerInterface
{
    /**
     * Handles callback request and returns array of DateTime, OrderId, Amount, Currency, OrderStatus values
     *
     * @return array
     */
    final public function handle(): array
    {
        $message = $_REQUEST['xmlmsg'] ?? null;
        if ($message === null || $message === '') {
            throw new EmptyXMLMessage();
        }
        if (!is_string($message)) {
            throw new InvalidXMLMessage();
        }

        $xmlmsg = @simplexml_load_string($message, \SimpleXMLElement::class, LIBXML_NONET);
        if ($xmlmsg === false) {
            $decoded = base64_decode($message, true);
            $xmlmsg = $decoded === false ? false : @simplexml_load_string($decoded, \SimpleXMLElement::class, LIBXML_NONET);
        }
        if ($xmlmsg === false || !isset($xmlmsg->TranDateTime, $xmlmsg->OrderID, $xmlmsg->PurchaseAmount, $xmlmsg->Currency, $xmlmsg->OrderStatus)) {
            throw new InvalidXMLMessage();
        }

        return [
            'DateTime'      => (string)$xmlmsg->TranDateTime,
            'OrderId'       => (string)$xmlmsg->OrderID,
            'Amount'        => (string)$xmlmsg->PurchaseAmount,
            'Currency'      => (string)$xmlmsg->Currency,
            'OrderStatus'   => (string)$xmlmsg->OrderStatus,
            'xmlmsg'        => $_REQUEST['xmlmsg']
        ];
    }
}
