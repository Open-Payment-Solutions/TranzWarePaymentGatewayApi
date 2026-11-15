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
     * @param string $url
     *
     * @return $this
     */
    public function setGatewayUrl(string $url): self;

    /**
     * @return string
     */
    public function getGatewayUrl(): string;

    /**
     * @param string $url
     *
     * @return $this
     */
    public function setOnOrderApprovedUrl(string $url): self;

    /**
     * @return string
     */
    public function getOnOrderApprovedUrl(): string;

    /**
     * @param string $url
     *
     * @return $this
     */
    public function setOnOrderDeclinedUrl(string $url): self;

    /**
     * @return string
     */
    public function getOnOrderDeclinedUrl(): string;

    /**
     * @param string $url
     *
     * @return this
     */
    public function setOnOrderCanceledUrl(string $url): self;

    /**
     * @return string
     */
    public function getOnOrderCanceledUrl(): string;
}
