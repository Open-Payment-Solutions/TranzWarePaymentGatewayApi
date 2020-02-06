<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Handlers;

/**
 * Class that handles request from TWPG after approved/canceled/declined order creation on TWPG site
 *
 * Class TranzWarePaymentGatewayOrderCallbackHandler
 *
 * @package OpenPaymentSolutions\TranzWarePaymentGateway\Handlers
 */
class TranzWarePaymentGatewayOrderCallbackHandler implements TranzWarePaymentGatewayHandlerInterface
{
    /**
     * Handles callback request and returns array of DatTime, OrderId, Amount, Currency, OrderStatus values
     *
     * @return array
     */
    final public function handle()
    {
        $xmlmsg = @simplexml_load_string($_REQUEST['xmlmsg']);
        if (!$xmlmsg) { $xmlmsg = @simplexml_load_string(base64_decode($_REQUEST['xmlmsg']));
        }
        if (!$xmlmsg) { return null;
        }

        $data = json_decode(
            json_encode(
                (array)$xmlmsg
            ),
            false
        );
        return [
            'DateTime'      => $data->TranDateTime,
            'OrderId'       => $data->OrderID,
            'Amount'        => $data->PurchaseAmount,
            'Currency'      => $data->Currency,
            'OrderStatus'   => $data->OrderStatus,
            'xmlmsg'        => $_REQUEST['xmlmsg']
        ];
    }
}