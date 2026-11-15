<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Requests;

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
    protected bool $strictSSL = true;
    protected ?string $debugToFile = null;
    protected mixed $debugFileDescriptor = null;

    /**
     * HTTPClient constructor.
     *
     * @param string $url
     * @param null   $body
     * @param null   $ssl
     * @param bool   $strictSSL
     */
    public function __construct(
        string $url,
        ?string $body = null,
        ?array $ssl = null,
        bool $strictSSL = true
    ) {
        $this->url = $url;
        $this->body = $body;
        $this->ssl = $ssl;
        $this->strictSSL = $strictSSL;
    }

    /**
     * Set debug to log file
     *
     * @param string $path_to_file
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
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_VERBOSE, $this->debug);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt(
            $ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/xml',
                'Content-Length: '.strlen($this->body ?? '')
            ]
        );
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->body);

        if ($this->ssl) {
            curl_setopt($ch, CURLOPT_SSLCERT, $this->ssl['cert']);
            curl_setopt($ch, CURLOPT_SSLKEY, $this->ssl['key']);

            if (isset($this->ssl['keyPass'])) {
                curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $this->ssl['keyPass']);
            }

            if (isset($this->ssl['ca'])) {
              $sslCAPath = $this->ssl['ca']['path'];
              $sslCAFile = $this->ssl['ca']['file'];
              curl_setopt($ch, CURLOPT_CAPATH, $sslCAPath);
              curl_setopt($ch, CURLOPT_CAINFO, $sslCAFile);
            }

            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, (bool)$this->strictSSL ? 2 : 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, (bool)$this->strictSSL);
        }

        if ($this->debug) {
            curl_setopt($ch, CURLOPT_STDERR, $this->debugFileDescriptor);
            fputs($this->debugFileDescriptor, "URL: " . $this->url);
            fputs($this->debugFileDescriptor, "BODY: \n" . var_export($this->body, true));
        }

        $output = curl_exec($ch);
        $info = curl_getinfo($ch);

        if ($this->debug) {
            fputs($this->debugFileDescriptor, "INFO: \n" . var_export($info, true));
            fputs($this->debugFileDescriptor, "OUTPUT: \n" . var_export($output, true));
            fflush($this->debugFileDescriptor);
        }

        return new HTTPClientResult(
            $output,
            $info
        );
    }
}
