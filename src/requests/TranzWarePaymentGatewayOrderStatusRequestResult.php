<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Requests;

/**
 * Class TranzWarePaymentGatewayOrderStatusRequestResult
 *
 * @package OpenPaymentSolutions\TranzWarePaymentGateway\Requests
 */
class TranzWarePaymentGatewayOrderStatusRequestResult implements TranzWarePaymentGatewayRequestResultInterface
{
    private $httpStatus;
    private $responseBody;
    private $status;
    private $data;

    /**
     * TranzWarePaymentGatewayOrderStatusRequestResult constructor.
     *
     * @param TranzWarePaymentGatewayHTTPClientResultInterface $HTTPClientResult
     */
    public function __construct(TranzWarePaymentGatewayHTTPClientResultInterface $HTTPClientResult)
    {
        $this->responseBody = $HTTPClientResult->getOutput();
        $info = $HTTPClientResult->getInfo();
        $this->httpStatus = $info['http_code'];

        if (!$this->responseBody) {
            $this->status = null;
            $this->data = [];
            return;
        }

        $this->data =
            json_decode(
                json_encode(
                    (array)simplexml_load_string($this->responseBody)
                ),
                false
            );

        $response = $this->data->Response;
        $order = $response->Order;
        $this->status = $response->Status;
        $this->data = [
            'OrderId'       => $order->OrderID,
            'OrderStatus'   => $order->OrderStatus
        ];
    }

    public function getHttpStatus()
    {
        return $this->httpStatus;
    }

    final public function getResponseBody()
    {
        $this->responseBody;
    }

    final public function success()
    {
        return $this->data['OrderStatus'] === 'APPROVED';
    }

    final public function getStatus()
    {
        return $this->status;
    }

    final public function getData()
    {
        return $this->data;
    }
}