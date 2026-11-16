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
     * @param string|null $body
     * @param array|null $sslCertificate
     * @param bool $strictSSL
     * @param string|null $userAgent
     */
    public function __construct(
        string $url,
        ?string $body = null,
        ?array $sslCertificate = null,
        bool $strictSSL = false,
        ?string $userAgent = null,
    );

    /**
     * Override the User-Agent or reset it to the package default with null.
     *
     * @param string|null $userAgent
     *
     * @return void
     */
    public function setUserAgent(?string $userAgent): void;

    /**
     * Set the HTTP debug output file.
     *
     * @param string $path_to_file
     *
     * @return void
     */
    public function setDebugToFile(string $path_to_file): void;

    /**
     * Execute the request.
     *
     * @return HTTPClientResultInterface
     */
    public function execute(): HTTPClientResultInterface;
}
