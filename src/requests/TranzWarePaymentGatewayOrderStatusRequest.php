<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Requests;

/**
 * Class TranzWarePaymentGatewayOrderStatusRequest
 * @package OpenPaymentSolutions\TranzWarePaymentGateway\Requests
 */
class TranzWarePaymentGatewayOrderStatusRequest implements TranzWarePaymentGatewayRequestInterface
{
    private $requestAttributes = [];
    private $debugToFile = null;

    /**
     * TranzWarePaymentGatewayOrderStatusRequest constructor.
     *
     * @param string $merchantId
     * @param string $requestUrl
     * @param string $orderId
     * @param string $sessionId
     * @param string $lang
     */
    public function __construct($requestUrl, $merchantId, $orderId, $sessionId, $lang = 'EN', $debugToFile)
    {
        $this->requestAttributes =
            compact('merchantId', 'requestUrl', 'orderId', 'sessionId', 'lang');
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
        return new TranzWarePaymentGatewayOrderStatusRequestResult($httpClient->execute());
    }

    final private function getRequestBody()
    {
        $templateFile = __DIR__ . '/templates/OrderStatusRequestBodyTemplate.xml';
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