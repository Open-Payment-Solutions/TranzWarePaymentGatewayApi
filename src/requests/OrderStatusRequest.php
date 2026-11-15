<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Requests;

use OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions\InvalidOrderId;
use OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions\InvalidSessionId;
use OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions\OrderStatusRetrieveException;

/**
 * Class OrderStatusRequest
 *
 * @package OpenPaymentSolutions\TranzWarePaymentGateway\Requests
 */
class OrderStatusRequest implements RequestInterface
{
    use RequestSettings;

    private array $requestAttributes = [];
    private ?string $debugToFile = null;

    /**
     * OrderStatusRequest constructor.
     *
     * @param string $merchantId
     * @param string $requestUrl
     * @param string|null $orderId
     * @param string|null $sessionId
     * @param string $lang (optional)
     * @param string $debugToFile (optional)
     * @throws InvalidOrderId
     * @throws InvalidSessionId
     */
    public function __construct(
        string $requestUrl,
        string $merchantId,
        ?string $orderId,
        ?string $sessionId,
        string $lang = 'EN',
        ?string $debugToFile = null
    ) {
        if ($orderId === null || trim($orderId) === '') {
            throw new InvalidOrderId();
        }
        if ($sessionId === null || trim($sessionId) === '') {
            throw new InvalidSessionId();
        }

        $this->requestAttributes =
            compact('merchantId', 'requestUrl', 'orderId', 'sessionId', 'lang');
        $this->debugToFile = $debugToFile;
    }

    /**
     * @throws OrderStatusRetrieveException
     */
    public function execute(): RequestResultInterface
    {
        try {
            $ssl = [
                'key' => $this->sslKey,
                'keyPass' => $this->sslKeyPass,
                'cert' => $this->sslCertificate
            ];
            $httpClient = new HTTPClient(
                $this->requestAttributes['requestUrl'],
                $this->getRequestBody(),
                $ssl,
                $this->strictSSL
            );
            if ($this->debugToFile) {
                $httpClient->setDebugToFile($this->debugToFile);
            }
            $httpResult = $httpClient->execute();
            if (($httpResult->getInfo()['http_code'] ?? 0) !== 200 || !$httpResult->getOutput()) {
                throw new \RuntimeException('Empty gateway response or unsuccessful HTTP status');
            }

            $result = new OrderStatusRequestResult($httpResult);
            if ($result->getStatus() !== '00' || ($result->getData()['OrderStatus'] ?? '') === '') {
                throw new \UnexpectedValueException('Gateway did not return an order status');
            }
            return $result;
        } catch (\Throwable $e) {
            throw new OrderStatusRetrieveException($e);
        }
    }

    private function getRequestBody(): string
    {
        $templateFile = __DIR__ . '/templates/OrderStatusRequestBodyTemplate.xml';
        $body = file_get_contents($templateFile);
        foreach ($this->requestAttributes as $key => $value) {
            $body = str_replace(
                '{{' . $key . '}}',
                htmlspecialchars((string)$value, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                $body
            );
        }
        return $body;
    }
}
