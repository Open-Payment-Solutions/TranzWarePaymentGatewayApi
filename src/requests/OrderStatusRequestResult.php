<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Requests;

use OpenPaymentSolutions\TranzWarePaymentGateway\Data\OrderStatusData;
use OpenPaymentSolutions\TranzWarePaymentGateway\Data\XMLData;
use Exception;

/**
 * Class OrderStatusRequestResult
 *
 * @package OpenPaymentSolutions\TranzWarePaymentGateway\Requests
 */
class OrderStatusRequestResult implements RequestResultInterface
{
    private int $httpStatus = 0;
    private string|false $responseBody = '';
    private ?string $status = null;
    private string $details = '';
    private OrderStatusData $data;

    /**
     * OrderStatusRequestResult constructor.
     *
     * @param HTTPClientResultInterface $HTTPClientResult
     *
     * @throws Exception
     */
    public function __construct(HTTPClientResultInterface $HTTPClientResult)
    {
        $this->data = new OrderStatusData();
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
        $this->details = (string)($response->Details ?? '');

        if (!isset($order->OrderStatus) && !isset($order->OrderID)) {
            if ($this->details) {
                throw new Exception($this->details);
            }

            throw new Exception(
                'Unhandled situation. Please inform about it by opening issue in repository. XML Body: '
                . $this->responseBody
            );
        }

        $this->data = new OrderStatusData(
            orderId: isset($order->OrderID) ? (string)$order->OrderID : null,
            orderStatus: isset($order->OrderStatus) ? (string)$order->OrderStatus : null,
            __unparsed: XMLData::unparsed($xml, [
                'Response' => [
                    'Status' => true,
                    'Details' => true,
                    'Order' => ['OrderID' => true, 'OrderStatus' => true],
                ],
            ])
        );
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
        return $this->data->orderStatus === 'APPROVED';
    }

    final public function getStatus(): ?string
    {
        return $this->status;
    }

    final public function getDetails(): string
    {
        return $this->details;
    }

    /**
     * Return the parsed gateway data and any unparsed fields.
     *
     * @return OrderStatusData
     */
    final public function getData(): OrderStatusData
    {
        return $this->data;
    }
}
