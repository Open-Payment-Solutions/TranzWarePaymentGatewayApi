<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway;

use OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions\InvalidOrderId;
use OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions\InvalidSessionId;
use OpenPaymentSolutions\TranzWarePaymentGateway\Requests\OrderRequest;
use OpenPaymentSolutions\TranzWarePaymentGateway\Requests\OrderStatusRequest;

/**
 * Factory class for creation of request objects
 *
 * Example:
 *  $requestFactory = new RequestFactory(
 *   'https://tranz-ware-payment-gateway/url',
 *   'E1000010',
 *   'https://your-site-address-here/samples/order_approved.php',
 *   'https://your-site-address-here/samples/order_declined.php',
 *   'https://your-site-address-here/samples/order_canceled.php',
 *   'EN'
 *  );
 *  $keyFile = __DIR__.'/../certificates/your-private-key.pem';
 *  $keyPass = file_get_contents(__DIR__.'/../certificates/your-private-key-pass.txt');
 *  $certFile = __DIR__.'/../certificates/cert-signed-by-payment-gateway-part.crt';
 *  $requestFactory->setCertificate($certFile, $keyFile, $keyPass);
 *  $requestFactory->enableSSLVerification();
 *
 *  $orderRequest = $requestFactory->createOrderRequest(1, CurrencyCodes::USD, 'TEST PAYMENT #1'); // --> instance of Requests\RequestInterface
 *
 * Class RequestFactory
 *
 * @package OpenPaymentSolutions\TranzWarePaymentGateway
 */
class RequestFactory implements RequestFactoryInterface
{
    /**
     * RequestFactory constructor.
     *
     * @param string $GATEWAY_URL
     * @param string $MERCHANT_ID
     * @param string $ON_ORDER_APPROVED_URL
     * @param string $ON_ORDER_DECLINED_URL
     * @param string $ON_ORDER_CANCELED_URL
     * @param string $LANG (optional)
     */
    public function __construct(
        string $GATEWAY_URL, string $MERCHANT_ID,
        string $ON_ORDER_APPROVED_URL, string $ON_ORDER_DECLINED_URL, string $ON_ORDER_CANCELED_URL,
        string $LANG = 'EN'
    ) {
        $this->MERCHANT_ID = $MERCHANT_ID;
        $this->LANG = $LANG;
        $urlProvider = new Urls();
        $urlProvider
            ->setGatewayUrl($GATEWAY_URL)
            ->setOnOrderApprovedUrl($ON_ORDER_APPROVED_URL)
            ->setOnOrderDeclinedUrl($ON_ORDER_DECLINED_URL)
            ->setOnOrderCanceledUrl($ON_ORDER_CANCELED_URL);
        $this->setUrlProvider($urlProvider);
    }

    private ?bool $strictSSL = null;
    private ?string $sslCertificate = null;
    private ?string $sslKey = null;
    private ?string $sslKeyPass = null;

    /**
     * Setting certificate, key file, key pass (default: ''), strict mode (default: enabled)
     *
     * @param string    $certFile  Path to certificate
     * @param string    $keyFile   Path to private key
     * @param string    $keyPass   (optional) Password provided in creation of private key
     * @param bool|null $strictSSL (optional) Enables or disables SSL host verification (default: enabled)
     *
     * @return RequestFactory
     */
    final public function setCertificate(string $certFile, string $keyFile, ?string $keyPass = '', ?bool $strictSSL = null): self
    {
        $this->sslKey = $keyFile;
        $this->sslKeyPass = $keyPass;
        $this->sslCertificate = $certFile;
        if (is_null($strictSSL)) {
            if (is_null($this->strictSSL)) {
                $this->enableSSLVerification();
            }
        }
        else {
            $this->strictSSL = (bool)$strictSSL;
        }

        return $this;
    }

    /**
     * Disables SSL host verification
     *
     * @return RequestFactory
     */
    final public function disableSSLVerification(): self
    {
        $this->strictSSL = false;
        return $this;
    }

    /**
     * Enables SSL host verification
     *
     * @return RequestFactory
     */
    final public function enableSSLVerification(): self
    {
        $this->strictSSL = true;
        return $this;
    }

    protected string $MERCHANT_ID;
    protected string $LANG;

    /**
     * @var UrlProviderInterface
     *
     * Instance of UrlProviderInterface
     * that returns set of urls required by request instances
     */
    protected UrlProviderInterface $urlProvider;

    /**
     * @param UrlProviderInterface $urlProvider
     *
     * @return RequestFactory
     */
    private function setUrlProvider(UrlProviderInterface $urlProvider): self
    {
        $this->urlProvider = $urlProvider;
        return $this;
    }

    /**
     * @return UrlProviderInterface
     */
    private function getUrlProvider(): UrlProviderInterface
    {
        return $this->urlProvider;
    }

    protected bool $debug = false;
    protected ?string $debugFile = null;

    /**
     * @param string $pathToFile
     *
     * @return RequestFactory
     */
    final public function setDebugFile(string $pathToFile): self
    {
        $this->debug = true;
        $this->debugFile = $pathToFile;
        return $this;
    }

    /**
     * @param float                                              $amount
     * @param CurrencyCodes|string                                             $currency
     * @param string                                             $description (optional)
     * @param OrderTypes|string $orderType   (optional)
     *
     * @return OrderRequest
     */
    final public function createOrderRequest(float $amount, CurrencyCodes|string $currency, string $description = '', OrderTypes|string $orderType = OrderTypes::PURCHASE): OrderRequest
    {
        $request = new OrderRequest(
            $this->getUrlProvider()->getGatewayUrl(),
            $this->getUrlProvider()->getOnOrderApprovedUrl(),
            $this->getUrlProvider()->getOnOrderDeclinedUrl(),
            $this->getUrlProvider()->getOnOrderCanceledUrl(),
            OrderTypes::fromString($orderType),
            $this->MERCHANT_ID,
            $amount,
            $currency,
            $description,
            $this->LANG,
            $this->debug ? $this->debugFile : null
        );
        $request->setSslCertificate($this->sslCertificate, $this->sslKey, $this->sslKeyPass, $this->strictSSL);
        return $request;
    }

    /**
     * @param float  $amount
     * @param CurrencyCodes|string $currency
     * @param string $description (optional)
     *
     * @return OrderRequest
     */
    final public function createOrderPreAuthRequest(float $amount, CurrencyCodes|string $currency, string $description = ''): OrderRequest
    {
        return $this->createOrderRequest($amount, $currency, $description, OrderTypes::PRE_AUTH);
    }

    /**
     * @param float  $amount
     * @param CurrencyCodes|string $currency
     * @param string $description (optional)
     *
     * @return OrderRequest
     */
    final public function createOrderPurchaseRequest(float $amount, CurrencyCodes|string $currency, string $description = ''): OrderRequest
    {
        return $this->createOrderRequest($amount, $currency, $description, OrderTypes::PURCHASE);
    }

  /**
   * @param string|null $orderId
   * @param string|null $sessionId
   *
   * @return OrderStatusRequest
   * @throws InvalidOrderId
   * @throws InvalidSessionId
   */
    final public function createOrderStatusRequest(?string $orderId, ?string $sessionId): OrderStatusRequest
    {
        $request = new OrderStatusRequest(
            $this->getUrlProvider()->getGatewayUrl(),
            $this->MERCHANT_ID,
            $orderId,
            $sessionId,
            $this->LANG,
            $this->debug ? $this->debugFile : null
        );
        $request->setSslCertificate($this->sslCertificate, $this->sslKey, $this->sslKeyPass, $this->strictSSL);
        return $request;
    }
}
