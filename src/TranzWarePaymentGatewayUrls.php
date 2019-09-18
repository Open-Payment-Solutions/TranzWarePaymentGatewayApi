<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway;

/***
 * Class for storing urls required by TWPG
 *
 * Example:
 *  $gatewayUrls = new TranzWarePaymentGatewayUrls();
 *  $gatewayUrls->setGatewayUrl('https://twpg/url/here');
 *  print $gatewayUrls->getGatewayUrl(); // --> https://twpg/url/here
 *
 * Class TranzWarePaymentGatewayUrls
 * @package OpenPaymentSolutions\TranzWarePaymentGateway
 */
class TranzWarePaymentGatewayUrls implements TranzWarePaymentGatewayUrlProviderInterface
{
    /**
     * @var string $GATEWAY_URL
     *
     * Url of payment gateway where requests
     * will go from us
     */
    protected $GATEWAY_URL;

    /**
     * @param $url
     *
     * Sets payment gateway url
     * and returns 'this' (for method chaining)
     *
     * @return $this
     */
    final public function setGatewayUrl($url)
    {
        $this->GATEWAY_URL = $url;
        return $this;
    }

    /**
     * Returns payment gateway url
     *
     * @return string
     */
    final public function getGatewayUrl()
    {
        return $this->GATEWAY_URL;
    }

    /**
     * @var string $ON_ORDER_APPROVED_URL
     *
     * The location where browser will be forwarded
     * in cases when payment approved by gateway
     * NOTE:
     *   "approved" does not mean it's been paid
     *   requires checking status of payment
     */
    protected $ON_ORDER_APPROVED_URL;

    /**
     * @param $url
     *
     * Sets order approval redirection url
     * and returns 'this' (for method chaining)
     *
     * @return $this
     */
    final public function setOnOrderApprovedUrl($url)
    {
        $this->ON_ORDER_APPROVED_URL = $url;
        return $this;
    }

    /**
     * Returns order approval redirection url
     *
     * @return string
     */
    final public function getOnOrderApprovedUrl()
    {
        return $this->ON_ORDER_APPROVED_URL;
    }

    /**
     * @var string $ON_ORDER_DECLINED_URL
     *
     * The location where browser will be forwarded
     * in cases when payment gateway decline order
     */
    protected $ON_ORDER_DECLINED_URL;

    /**
     * @param $url
     *
     * Sets order decline redirection url
     * and returns 'this' (for method chaining)
     *
     * @return $this
     */
    final public function setOnOrderDeclinedUrl($url)
    {
        $this->ON_ORDER_DECLINED_URL = $url;
        return $this;
    }

    /**
     * Returns order decline redirection url
     *
     * @return string
     */
    final public function getOnOrderDeclinedUrl()
    {
        return $this->ON_ORDER_DECLINED_URL;
    }

    /**
     * @var string $ON_ORDER_CANCELED_URL
     *
     * The location where browser will be forwarded
     * in cases when order will be cancelled by user
     */
    protected $ON_ORDER_CANCELED_URL;

    /**
     * @param $url
     *
     * Sets order cancellation redirection url
     * and returns 'this' (for method chaining)
     *
     * @return $this
     */
    final public function setOnOrderCanceledUrl($url)
    {
        $this->ON_ORDER_CANCELED_URL = $url;
        return $this;
    }

    /**
     * Returns order cancellation redirection url
     *
     * @return string
     */
    final public function getOnOrderCanceledUrl()
    {
        return $this->ON_ORDER_CANCELED_URL;
    }
}