<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway;

use \Exception;
use \OpenPaymentSolutions\TranzWarePaymentGateway\Requests\TranzWarePaymentGatewayOrderRequest;
use \OpenPaymentSolutions\TranzWarePaymentGateway\Requests\TranzWarePaymentGatewayOrderStatusRequest;

/**
 * Factory class for creation of request objects
 *
 * Example:
 *  $requestFactory = new TranzWarePaymentGatewayRequestFactory(
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
 *  $requestFactory->disableSSLVerification();
 *
 *  $orderRequest = $requestFactory->createOrderRequest(1, 'USD', 'TEST PAYMENT #1'); // --> instance of TranzWarePaymentGatewayRequestInterface
 *
 * Class TranzWarePaymentGatewayRequestFactory
 *
 * @package OpenPaymentSolutions\TranzWarePaymentGateway
 */
class TranzWarePaymentGatewayRequestFactory implements TranzWarePaymentGatewayRequestFactoryInterface
{
    /**
     * TranzWarePaymentGatewayRequestFactory constructor.
     *
     * @param string $GATEWAY_URL
     * @param string $MERCHANT_ID
     * @param string $ON_ORDER_APPROVED_URL
     * @param string $ON_ORDER_DECLINED_URL
     * @param string $ON_ORDER_CANCELED_URL
     * @param string $LANG (optional)
     */
    public function __construct(
        $GATEWAY_URL, $MERCHANT_ID,
        $ON_ORDER_APPROVED_URL, $ON_ORDER_DECLINED_URL, $ON_ORDER_CANCELED_URL,
        $LANG = 'EN'
    ) {
        $this->MERCHANT_ID = $MERCHANT_ID;
        $this->LANG = $LANG;
        $urlProvider = new TranzWarePaymentGatewayUrls();
        $urlProvider
            ->setGatewayUrl($GATEWAY_URL)
            ->setOnOrderApprovedUrl($ON_ORDER_APPROVED_URL)
            ->setOnOrderDeclinedUrl($ON_ORDER_DECLINED_URL)
            ->setOnOrderCanceledUrl($ON_ORDER_CANCELED_URL);
        $this->setUrlProvider($urlProvider);
    }

    private $strictSSL = null, $sslCertificate, $sslKey, $sslKeyPass;

    /**
     * Setting certificate, key file, key pass (default: ''), strict mode (default: enabled)
     *
     * @param string    $certFile  Path to certificate
     * @param string    $keyFile   Path to private key
     * @param string    $keyPass   (optional) Password provided in creation of private key
     * @param bool|null $strictSSL (optional) Enables or disables SSL host verification (default: enabled)
     *
     * @return TranzWarePaymentGatewayRequestFactory
     */
    final public function setCertificate($certFile, $keyFile, $keyPass = '', $strictSSL = null)
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
     * @return TranzWarePaymentGatewayRequestFactory
     */
    final public function disableSSLVerification()
    {
        $this->strictSSL = false;
        return $this;
    }

    /**
     * Enables SSL host verification
     *
     * @return TranzWarePaymentGatewayRequestFactory
     */
    final public function enableSSLVerification()
    {
        $this->strictSSL = true;
        return $this;
    }

    protected $MERCHANT_ID;
    protected $LANG;

    /**
     * @var TranzWarePaymentGatewayUrlProviderInterface
     *
     * Instance of TranzWarePaymentGatewayUrlProviderInterface
     * that returns set of urls required by request instances
     */
    protected $urlProvider;

    /**
     * @param TranzWarePaymentGatewayUrlProviderInterface $urlProvider
     *
     * @return TranzWarePaymentGatewayRequestFactory
     */
    final private function setUrlProvider(TranzWarePaymentGatewayUrlProviderInterface $urlProvider)
    {
        $this->urlProvider = $urlProvider;
        return $this;
    }

    /**
     * @return TranzWarePaymentGatewayUrlProviderInterface
     */
    final private function getUrlProvider()
    {
        return $this->urlProvider;
    }

    protected $debug = false, $debugFile;

    /**
     * @param string $pathToFile
     *
     * @return TranzWarePaymentGatewayRequestFactory
     */
    final public function setDebugFile($pathToFile)
    {
        $this->debug = true;
        $this->debugFile = $pathToFile;
        return $this;
    }

    /**
     * @param float                                              $amount
     * @param string                                             $currency
     * @param string                                             $description (optional)
     * @param string{OrderTypes::PURCHASE, OrderTypes::PRE_AUTH} $orderType   (optional)
     *
     * @return TranzWarePaymentGatewayOrderRequest
     */
    final public function createOrderRequest($amount, $currency, $description = '', $orderType = OrderTypes::PURCHASE)
    {
        $request = new TranzWarePaymentGatewayOrderRequest(
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
     * @param string $currency
     * @param string $description (optional)
     *
     * @return TranzWarePaymentGatewayOrderRequest
     */
    final public function createOrderPreAuthRequest($amount, $currency, $description = '')
    {
        return $this->createOrderRequest($amount, $currency, $description, OrderTypes::PRE_AUTH);
    }

    /**
     * @param float  $amount
     * @param string $currency
     * @param string $description (optional)
     *
     * @return TranzWarePaymentGatewayOrderRequest
     */
    final public function createOrderPurchaseRequest($amount, $currency, $description = '')
    {
        return $this->createOrderRequest($amount, $currency, $description, OrderTypes::PURCHASE);
    }

  /**
   * @param string $orderId
   * @param string $sessionId
   *
   * @return TranzWarePaymentGatewayOrderStatusRequest
   * @throws Exception
   */
    final public function createOrderStatusRequest($orderId, $sessionId)
    {
        if (!$orderId) {
          throw new Exception('Empty OrderID');
        }

        if (!$sessionId) {
          throw new Exception('Empty SessionID');
        }

        $request = new TranzWarePaymentGatewayOrderStatusRequest(
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