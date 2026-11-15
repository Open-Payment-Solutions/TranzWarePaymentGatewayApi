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
    public function __construct(
        string $url,
        ?string $body = null,
        ?array $sslCertificate = null,
        bool $strictSSL = true,
    );

    /**
     * @param  string $path_to_file
     * @return void
     */
    public function setDebugToFile(string $path_to_file): void;

    /**
     * @return HTTPClientResultInterface
     */
    public function execute(): HTTPClientResultInterface;
}
