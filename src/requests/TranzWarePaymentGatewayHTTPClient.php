<?php
namespace OpenPaymentSolutions\TranzWarePaymentGateway\Requests;

/**
 * Class TranzWarePaymentGatewayHTTPClient
 * @package OpenPaymentSolutions\TranzWarePaymentGateway\Requests
 */
class TranzWarePaymentGatewayHTTPClient implements TranzWarePaymentGatewayHTTPClientInterface
{
    protected $url;
    protected $body;
    protected $sslCertificate;
    protected $debug = false;
    protected $debugToFile;

    /**
     * TranzWarePaymentGatewayHTTPClient constructor.
     *
     * @param string $url              Destination request url
     * @param string $body             Body that will be delivered to destination
     * @param array  $sslCertificate   Array of 'key', 'keyPass', 'cert'  values
     */
    public function __construct
    (
        $url,
        $body = null,
        $sslCertificate = null
    )
    {
        $this->url = $url;
        $this->body = $body;
        $this->sslCertificate = $sslCertificate;
    }

    final public function setDebugToFile($path_to_file)
    {
        $this->debug = true;
        $this->debugToFile = $path_to_file;
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
        if ($this->debug) {
            curl_setopt($ch, CURLOPT_STDERR, fopen($this->debugToFile, 'w+'));
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/xml',
            'Content-Length: '.strlen($this->body)
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->body);

        if ($this->sslCertificate) {
            $key = $this->sslCertificate['key'];
            $keyPass = $this->sslCertificate['keyPass'];
            $cert = $this->sslCertificate['cert'];
            curl_setopt($ch, CURLOPT_SSLCERT, $cert);
            curl_setopt($ch, CURLOPT_SSLKEY, $key);
            curl_setopt($ch, CURLOPT_SSLKEYPASSWD, $keyPass);
            curl_setopt($ch, CURLOPT_CAINFO, $cert);
            curl_setopt($ch, CURLOPT_CAPATH, $cert);
        }

        $output = curl_exec($ch);
        $info = curl_getinfo($ch);
        return new TranzWarePaymentGatewayHTTPClientResult(
            $output,
            $info
        );
    }
}