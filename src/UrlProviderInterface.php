<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway;

/**
 * Interface UrlProviderInterface
 *
 * @package OpenPaymentSolutions\TranzWarePaymentGateway
 */
interface UrlProviderInterface
{
    /**
     * Set the payment gateway URL.
     *
     * @param string $url
     *
     * @return $this
     */
    public function setGatewayUrl(string $url): self;

    /**
     * Return the payment gateway URL.
     *
     * @return string
     */
    public function getGatewayUrl(): string;

    /**
     * Set the order approval URL.
     *
     * @param string $url
     *
     * @return $this
     */
    public function setOnOrderApprovedUrl(string $url): self;

    /**
     * Return the order approval URL.
     *
     * @return string
     */
    public function getOnOrderApprovedUrl(): string;

    /**
     * Set the order decline URL.
     *
     * @param string $url
     *
     * @return $this
     */
    public function setOnOrderDeclinedUrl(string $url): self;

    /**
     * Return the order decline URL.
     *
     * @return string
     */
    public function getOnOrderDeclinedUrl(): string;

    /**
     * Set the order cancellation URL.
     *
     * @param string $url
     *
     * @return $this
     */
    public function setOnOrderCanceledUrl(string $url): self;

    /**
     * Return the order cancellation URL.
     *
     * @return string
     */
    public function getOnOrderCanceledUrl(): string;
}
