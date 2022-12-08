<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Requests;

use \Exception;

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
    private $details;
    private $data;

  /**
   * TranzWarePaymentGatewayOrderStatusRequestResult constructor.
   *
   * @param TranzWarePaymentGatewayHTTPClientResultInterface $HTTPClientResult
   *
   * @throws Exception
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
        $this->details = isset($response->Details) ? $response->Details : '';

        if (!isset($order->OrderStatus) && !isset($order->OrderID)) {
          if ($this->details) {
            throw new Exception($this->details);
          }

          throw new Exception('Unhandled situation. Please inform about it by opening issue in repository. XML Body: '.$this->responseBody);
        }

        $this->data = [
            'OrderId'       => isset($order->OrderId) ? $order->OrderId : null,
            'OrderStatus'   => isset($order->OrderStatus) ? $order->OrderStatus : null
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

    final public function getDetails()
    {
      return $this->details;
    }

    final public function getData()
    {
        return $this->data;
    }
}