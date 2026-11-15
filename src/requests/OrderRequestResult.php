<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Requests;

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
    private ?array $data = [];

    /**
     * OrderRequestResult constructor.
     *
     * @param HTTPClientResultInterface $HTTPClientResult
     */
    public function __construct(HTTPClientResultInterface $HTTPClientResult)
    {
        $this->responseBody = $HTTPClientResult->getOutput();
        $info = $HTTPClientResult->getInfo();
        $this->httpStatus = (int)($info['http_code'] ?? 0);

        if (!$this->responseBody) {
            $this->status = null;
            $this->data = [];
            return;
        }

        $xml = @simplexml_load_string($this->responseBody, \SimpleXMLElement::class, LIBXML_NONET);
        if ($xml === false || !isset($xml->Response->Status)) {
            throw new \UnexpectedValueException('Invalid gateway response');
        }

        $response = $xml->Response;
        $order = $response->Order ?? new \stdClass();
        $this->status = (string)$response->Status;

        $this->data = null;
        if ($this->success()) {
            if (!isset($order->URL, $order->OrderID, $order->SessionID)) {
                throw new \UnexpectedValueException('Incomplete gateway order response');
            }
            $this->data = [
                'URL' => (string)$order->URL,
                'OrderId' => (string)$order->OrderID,
                'SessionId' => (string)$order->SessionID,
                'PaymentUrl' => $order->URL . '?' .
                                'ORDERID=' . $order->OrderID . '&' .
                                'SESSIONID=' . $order->SessionID
            ];
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

    final public function getData(): ?array
    {
        return $this->data;
    }
}
