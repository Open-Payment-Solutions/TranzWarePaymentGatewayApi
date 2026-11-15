<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway;

/***
 * Class for storing urls required by TWPG
 *
 * Example:
 *  $gatewayUrls = new Urls();
 *  $gatewayUrls->setGatewayUrl('https://twpg/url/here');
 *  print $gatewayUrls->getGatewayUrl(); // --> https://twpg/url/here
 *
 * Class Urls
 *
 * @package OpenPaymentSolutions\TranzWarePaymentGateway
 */
class Urls implements UrlProviderInterface
{
    /**
     * Url of payment gateway where requests
     * will go from us
     *
     * @var string $GATEWAY_URL
     */
    protected string $GATEWAY_URL = '';

    /**
     * Sets payment gateway url
     * and returns 'this' (for method chaining)
     *
     * @param string $url
     *
     * @return $this
     */
    final public function setGatewayUrl(string $url): self
    {
        $this->GATEWAY_URL = $url;
        return $this;
    }

    /**
     * Returns payment gateway url
     *
     * @return string
     */
    final public function getGatewayUrl(): string
    {
        return $this->GATEWAY_URL;
    }

    /**
     * The location where browser will be forwarded
     * in cases when payment approved by gateway
     * NOTE:
     *   "approved" does not mean it's been paid
     *   requires checking status of payment
     *
     * @var string $ON_ORDER_APPROVED_URL
     */
    protected string $ON_ORDER_APPROVED_URL = '';

    /**
     * Sets order approval redirection url
     * and returns 'this' (for method chaining)
     *
     * @param string $url
     *
     * @return $this
     */
    final public function setOnOrderApprovedUrl(string $url): self
    {
        $this->ON_ORDER_APPROVED_URL = $url;
        return $this;
    }

    /**
     * Returns order approval redirection url
     *
     * @return string
     */
    final public function getOnOrderApprovedUrl(): string
    {
        return $this->ON_ORDER_APPROVED_URL;
    }

    /**
     * The location where browser will be forwarded
     * in cases when payment gateway decline order
     *
     * @var string $ON_ORDER_DECLINED_URL
     */
    protected string $ON_ORDER_DECLINED_URL = '';

    /**
     * Sets order decline redirection url
     * and returns 'this' (for method chaining)
     *
     * @param string $url
     *
     * @return $this
     */
    final public function setOnOrderDeclinedUrl(string $url): self
    {
        $this->ON_ORDER_DECLINED_URL = $url;
        return $this;
    }

    /**
     * Returns order decline redirection url
     *
     * @return string
     */
    final public function getOnOrderDeclinedUrl(): string
    {
        return $this->ON_ORDER_DECLINED_URL;
    }

    /**
     * The location where browser will be forwarded
     * in cases when order will be cancelled by user
     *
     * @var string $ON_ORDER_CANCELED_URL
     */
    protected string $ON_ORDER_CANCELED_URL = '';

    /**
     * Sets order cancellation redirection url
     * and returns 'this' (for method chaining)
     *
     * @param string $url
     *
     * @return $this
     */
    final public function setOnOrderCanceledUrl(string $url): self
    {
        $this->ON_ORDER_CANCELED_URL = $url;
        return $this;
    }

    /**
     * Returns order cancellation redirection url
     *
     * @return string
     */
    final public function getOnOrderCanceledUrl(): string
    {
        return $this->ON_ORDER_CANCELED_URL;
    }
}
