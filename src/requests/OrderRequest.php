<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Requests;

use OpenPaymentSolutions\TranzWarePaymentGateway\OrderTypes;
use OpenPaymentSolutions\TranzWarePaymentGateway\CurrencyCodes;

/**
 * Class OrderRequest
 *
 * @package OpenPaymentSolutions\TranzWarePaymentGateway\Requests
 */
class OrderRequest implements RequestInterface
{
    use RequestSettings;

    private array $requestAttributes = [];
    private ?string $debugToFile = null;

    /**
     * OrderRequest constructor.
     *
     * @param string $requestUrl
     * @param string $approvalUrl
     * @param string $declineUrl
     * @param string $cancelUrl
     * @param OrderTypes|string $orderType
     * @param string $merchantId
     * @param float $amount
     * @param CurrencyCodes|string $currency
     * @param string $description
     * @param string $lang
     * @param string|null $debugToFile
     */
    public function __construct(
        string $requestUrl,
        string $approvalUrl,
        string $declineUrl,
        string $cancelUrl,
        OrderTypes|string $orderType,
        string $merchantId,
        float $amount,
        CurrencyCodes|string $currency,
        string $description = '',
        string $lang = 'EN',
        ?string $debugToFile = null
    ) {
        $orderType = OrderTypes::fromString($orderType)->value;
        $currency = $currency instanceof CurrencyCodes ? $currency->value : $currency;
        $this->requestAttributes =
            compact(
                'requestUrl',
                'approvalUrl',
                'declineUrl',
                'cancelUrl',
                'orderType',
                'merchantId',
                'amount',
                'currency',
                'description',
                'lang'
            );
        $this->debugToFile = $debugToFile;
    }

    public function execute(): OrderRequestResult
    {
        $ssl = [
            'key' => $this->sslKey,
            'keyPass' => $this->sslKeyPass,
            'cert' => $this->sslCertificate
        ];
        $httpClient = new HTTPClient(
            $this->requestAttributes['requestUrl'],
            $this->getRequestBody(),
            $ssl,
            $this->strictSSL,
            $this->userAgent
        );
        if ($this->debugToFile) {
            $httpClient->setDebugToFile($this->debugToFile);
        }
        return new OrderRequestResult($httpClient->execute());
    }

    private function getRequestBody(): string
    {
        $orderType = OrderTypes::fromString($this->requestAttributes['orderType'])->value;
        $templateFile = __DIR__ . '/templates/' . $orderType . 'OrderRequestBodyTemplate.xml';
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
