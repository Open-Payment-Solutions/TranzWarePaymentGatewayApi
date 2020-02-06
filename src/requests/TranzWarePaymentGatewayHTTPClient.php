<?php
namespace OpenPaymentSolutions\TranzWarePaymentGateway\Requests;

/**
 * Class TranzWarePaymentGatewayHTTPClient
 *
 * @package OpenPaymentSolutions\TranzWarePaymentGateway\Requests
 */
class TranzWarePaymentGatewayHTTPClient implements TranzWarePaymentGatewayHTTPClientInterface
{
    protected $url;
    protected $body;
    protected $ssl;
    protected $debug = false;
    protected $strictSSL = true;
    protected $debugToFile;
    protected $debugFileDescriptor;

    /**
     * TranzWarePaymentGatewayHTTPClient constructor.
     *
     * @param string $url
     * @param null   $body
     * @param null   $ssl
     * @param bool   $strictSSL
     */
    public function __construct(
        $url,
        $body = null,
        $ssl = null,
        $strictSSL = true
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
    final public function setDebugToFile($path_to_file)
    {
        if (is_string($path_to_file)) {
            $this->debug = true;
            $this->debugToFile = $path_to_file;
            $this->debugFileDescriptor = fopen($path_to_file, 'w+');
        }
    }

    /**
     * Executes request and returns instance of result object
     *
     * @return TranzWarePaymentGatewayHTTPClientResult
     */
    final public function execute()
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
                'Content-Length: '.strlen($this->body)
            ]
        );
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->body);

        if ($this->ssl) {
            $sslCert = $this->ssl['cert'];
            $sslKeyPass = isset($this->ssl['keyPass']) ? $this->ssl['keyPass'] : '';
            curl_setopt($ch, CURLOPT_SSLCERT, $sslCert);
            if ($sslKeyPass) {
                curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $sslKeyPass);
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
        }

        return new TranzWarePaymentGatewayHTTPClientResult(
            $output,
            $info
        );
    }
}