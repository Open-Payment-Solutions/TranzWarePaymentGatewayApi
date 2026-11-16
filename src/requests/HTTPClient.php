<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Requests;

use OpenPaymentSolutions\TranzWarePaymentGateway\TransportFactory;

use function OpenPaymentSolutions\TranzWarePaymentGateway\getUserAgent;

/**
 * Class HTTPClient
 *
 * @package OpenPaymentSolutions\TranzWarePaymentGateway\Requests
 */
class HTTPClient implements HTTPClientInterface
{
    protected string $url;
    protected ?string $body;
    protected ?array $ssl;
    protected bool $debug = false;
    protected bool $strictSSL = false;
    protected ?string $debugToFile = null;
    protected mixed $debugFileDescriptor = null;
    protected ?string $userAgent = null;

    /**
     * HTTPClient constructor.
     *
     * @param string $url
     * @param string|null $body
     * @param array|null $ssl
     * @param bool $strictSSL
     * @param string|null $userAgent
     */
    public function __construct(
        string $url,
        ?string $body = null,
        ?array $ssl = null,
        bool $strictSSL = false,
        ?string $userAgent = null
    ) {
        $this->url = $url;
        $this->body = $body;
        $this->ssl = $ssl;
        $this->strictSSL = $strictSSL;
        $this->setUserAgent($userAgent);
    }

    /**
     * Override the User-Agent or reset it to the package default with null.
     *
     * @param string|null $userAgent
     *
     * @return void
     */
    final public function setUserAgent(?string $userAgent): void
    {
        $this->userAgent = $userAgent === null ? null : getUserAgent($userAgent);
    }

    /**
     * Set debug to log file
     *
     * @param string $path_to_file
     *
     * @return void
     */
    final public function setDebugToFile(string $path_to_file): void
    {
        $descriptor = @fopen($path_to_file, 'w+');
        if ($descriptor === false) {
            throw new \RuntimeException('Unable to open debug file: ' . $path_to_file);
        }
        if (is_resource($this->debugFileDescriptor)) {
            fclose($this->debugFileDescriptor);
        }
        $this->debug = true;
        $this->debugToFile = $path_to_file;
        $this->debugFileDescriptor = $descriptor;
    }

    public function __destruct()
    {
        if (is_resource($this->debugFileDescriptor)) {
            fclose($this->debugFileDescriptor);
        }
    }

    /**
     * Executes request and returns instance of result object
     *
     * @return HTTPClientResult
     */
    final public function execute(): HTTPClientResultInterface
    {
        $transport = (new TransportFactory())->create(
            $this->url,
            $this->body,
            $this->ssl,
            $this->strictSSL,
            $this->userAgent
        );

        if ($this->debug) {
            fputs($this->debugFileDescriptor, "URL: " . $this->url);
            fputs($this->debugFileDescriptor, "BODY: \n" . var_export($this->body, true));
        }

        $result = $transport->execute();

        if ($this->debug) {
            fputs($this->debugFileDescriptor, "INFO: \n" . var_export($result->getInfo(), true));
            fputs($this->debugFileDescriptor, "OUTPUT: \n" . var_export($result->getOutput(), true));
            fflush($this->debugFileDescriptor);
        }

        return $result;
    }
}
