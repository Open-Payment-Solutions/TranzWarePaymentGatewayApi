<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Requests;

/**
 * Interface TranzWarePaymentGatewayHTTPClientInterface
 * @package OpenPaymentSolutions\TranzWarePaymentGateway\Requests
 */
interface TranzWarePaymentGatewayHTTPClientInterface
{
    /**
     * TranzWarePaymentGatewayHTTPClientInterface constructor.
     *
     * @param string $url
     * @param null   $body
     * @param null   $sslCertificate
     * @param bool $strictSSL
     */
    public function __construct($url, $body = null, $sslCertificate = null, $strictSSL = true);

    /**
     * @param string $path_to_file
     * @return void
     */
    public function setDebugToFile($path_to_file);

    /**
     * @return TranzWarePaymentGatewayHTTPClientResultInterface
     */
    public function execute();
}