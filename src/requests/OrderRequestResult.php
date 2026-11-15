<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Requests;

use OpenPaymentSolutions\TranzWarePaymentGateway\Data\OrderData;
use OpenPaymentSolutions\TranzWarePaymentGateway\Data\XMLData;

/**
 * Class OrderRequestResult
 *
 * @package OpenPaymentSolutions\TranzWarePaymentGateway\Requests
 */
class OrderRequestResult implements RequestResultInterface
{
    private int $httpStatus = 0;
    private string|false $responseBody = '';
    private ?string $status = null;
    private OrderData $data;

    /**
     * OrderRequestResult constructor.
     *
     * @param HTTPClientResultInterface $HTTPClientResult
     */
    public function __construct(HTTPClientResultInterface $HTTPClientResult)
    {
        $this->data = new OrderData();
        $this->responseBody = $HTTPClientResult->getOutput();
        $info = $HTTPClientResult->getInfo();
        $this->httpStatus = $info->httpCode;

        if (!$this->responseBody) {
            $this->status = null;
            return;
        }

        $xml = @simplexml_load_string($this->responseBody, \SimpleXMLElement::class, LIBXML_NONET);
        if ($xml === false || !isset($xml->Response->Status)) {
            throw new \UnexpectedValueException('Invalid gateway response');
        }

        $response = $xml->Response;
        $order = $response->Order ?? new \stdClass();
        $this->status = (string)$response->Status;

        $unparsed = XMLData::unparsed($xml, [
            'Response' => ['Status' => true, 'Order' => ['URL' => true, 'OrderID' => true, 'SessionID' => true]],
        ]);
        $this->data = new OrderData(__unparsed: $unparsed);
        if ($this->success()) {
            if (!isset($order->URL, $order->OrderID, $order->SessionID)) {
                throw new \UnexpectedValueException('Incomplete gateway order response');
            }
            $this->data = new OrderData(
                url: (string)$order->URL,
                orderId: (string)$order->OrderID,
                sessionId: (string)$order->SessionID,
                paymentUrl: $order->URL . '?ORDERID=' . $order->OrderID . '&SESSIONID=' . $order->SessionID,
                __unparsed: $unparsed
            );
        }
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    final public function getResponseBody(): string|false
    {
        return $this->responseBody;
    }

    final public function success(): bool
    {
        return $this->status === '00';
    }

    final public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * Return the parsed gateway data and any unparsed fields.
     *
     * @return OrderData
     */
    final public function getData(): OrderData
    {
        return $this->data;
    }
}
