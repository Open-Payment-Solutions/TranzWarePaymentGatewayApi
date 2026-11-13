<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Requests;

/**
 * Interface HTTPClientInterface
 *
 * @package OpenPaymentSolutions\TranzWarePaymentGateway\Requests
 */
interface HTTPClientInterface
{
    /**
     * HTTPClientInterface constructor.
     *
     * @param string $url
     * @param null   $body
     * @param null   $sslCertificate
     * @param bool   $strictSSL
     */
    public function __construct($url, $body = null, $sslCertificate = null, $strictSSL = true);

    /**
     * @param  string $path_to_file
     * @return void
     */
    public function setDebugToFile($path_to_file);

    /**
     * @return HTTPClientResultInterface
     */
    public function execute();
}