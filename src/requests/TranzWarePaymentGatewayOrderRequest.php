<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Requests;

use \OpenPaymentSolutions\TranzWarePaymentGateway\OrderTypes;

/**
 * Class TranzWarePaymentGatewayOrderRequest
 * @package OpenPaymentSolutions\TranzWarePaymentGateway\Requests
 */
class TranzWarePaymentGatewayOrderRequest implements TranzWarePaymentGatewayRequestInterface
{
    private $requestAttributes = [];
    private $debugToFile = null;

    /**
     * TranzWarePaymentGatewayOrderRequest constructor.
     *
     * @param string $requestUrl
     * @param string $approvalUrl
     * @param string $declineUrl
     * @param string $cancelUrl
     * @param string{OrderTypes::PURCHASE, OrderTypes::PRE_AUTH} $orderType
     * @param string $merchantId
     * @param float  $amount
     * @param string $currency
     * @param string $description
     * @param string $lang
     * @param string $debugToFile
     */
    public function __construct(
        $requestUrl, $approvalUrl, $declineUrl, $cancelUrl,
        $orderType, $merchantId, $amount, $currency,
        $description = '', $lang = 'EN', $debugToFile = null
    )
    {
        $this->requestAttributes =
            compact('requestUrl', 'approvalUrl', 'declineUrl', 'cancelUrl', 'orderType', 'merchantId', 'amount', 'currency', 'description', 'lang');
        $this->debugToFile = $debugToFile;
    }

    public function execute()
    {
        $ssl = [
            'key' => $this->sslKey,
            'keyPass' => $this->sslKeyPass,
            'cert' => $this->sslCertificate
        ];
        $httpClient =
            new TranzWarePaymentGatewayHTTPClient($this->requestAttributes['requestUrl'], $this->getRequestBody(), $ssl);
        if ($this->debugToFile) {
            $httpClient->setDebugToFile($this->debugToFile);
        }
        return new TranzWarePaymentGatewayOrderRequestResult($httpClient->execute());
    }

    final private function getRequestBody()
    {
        $orderType = OrderTypes::fromString($this->requestAttributes['orderType']);
        $templateFile = __DIR__ . '/templates/'.$orderType.'OrderRequestBodyTemplate.xml';
        $body = file_get_contents($templateFile);
        foreach ($this->requestAttributes AS $key => $value) {
            $body = str_replace('{{' . $key . '}}', $value, $body);
        }
        return $body;
    }

    private $sslKey, $sslKeyPass, $sslCertificate;

    final public function setSslCertificate($cert, $key, $keyPass = '')
    {
        $this->sslKey = $key;
        $this->sslKeyPass = $keyPass;
        $this->sslCertificate = $cert;
    }
}