<?php

namespace tests\integration;

use OpenPaymentSolutions\TranzWarePaymentGateway\Requests\HTTPClient;
use OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions\TransportNotAvailable;
use OpenPaymentSolutions\TranzWarePaymentGateway\TransportFactory;
use OpenPaymentSolutions\TranzWarePaymentGateway\Transports\CurlTransport;
use OpenPaymentSolutions\TranzWarePaymentGateway\Transports\HTTPTransport;
use OpenPaymentSolutions\TranzWarePaymentGateway\Transports\HTTPSTransport;

require __DIR__ . '/../../../vendor/autoload.php';

/**
 * Fail immediately when a real transport check does not pass.
 *
 * @param bool $passed
 * @param string $description
 *
 * @return void
 */
$check = static function (bool $passed, string $description): void {
    if (!$passed) {
        throw new \RuntimeException('FAIL: ' . $description);
    }
    echo 'PASS: ' . $description . PHP_EOL;
};

$factory = new TransportFactory();
if (in_array('--curl-without-openssl', $argv, true)) {
    $check(
        extension_loaded('curl') && !extension_loaded('openssl'),
        'cURL is enabled without the PHP OpenSSL extension'
    );
    $check(
        $factory->create('https://server:8443/echo')::class === CurlTransport::class,
        'factory selects cURL for TLS'
    );
    $client = new HTTPClient('https://server:8443/echo', null, ['ca' => ['file' => '/certs/ca.crt']], true);
    $result = $client->execute();
    $check($result->getInfo()->httpCode === 200, 'cURL verifies HTTPS without the PHP OpenSSL extension');
    exit;
}
if (in_array('--missing-openssl', $argv, true)) {
    $check(!extension_loaded('curl') && !extension_loaded('openssl'), 'cURL and OpenSSL extensions are absent');
    $check($factory->create('http://server:8080/echo')::class === HTTPTransport::class, 'HTTP does not require TLS');
    try {
        $factory->create('https://server:8443/echo');
        throw new \RuntimeException('Expected TransportNotAvailable.');
    } catch (TransportNotAvailable $exception) {
        $check(str_contains($exception->getMessage(), 'OpenSSL'), 'HTTPS exception explains missing OpenSSL');
    }
    exit;
}
$withCurl = getenv('TWPG_TEST_WITH_CURL') === '1';
$check(extension_loaded('curl') === $withCurl, 'cURL extension matches the requested test configuration');
if (in_array('--disabled-wrapper', $argv, true)) {
    if ($withCurl) {
        $result = (new HTTPClient('http://server:8080/echo'))->execute();
        $check($result->getInfo()->httpCode === 200, 'cURL works with allow_url_fopen disabled');
    } else {
        try {
            (new HTTPClient('http://server:8080/echo'))->execute();
            throw new \RuntimeException('Expected TransportNotAvailable.');
        } catch (TransportNotAvailable $exception) {
            $check(
                str_contains($exception->getMessage(), 'allow_url_fopen'),
                'exception explains disabled URL wrappers'
            );
        }
    }
    exit;
}
if (in_array('--disabled-stream-functions', $argv, true)) {
    if ($withCurl) {
        $result = (new HTTPClient('http://server:8080/echo'))->execute();
        $check($result->getInfo()->httpCode === 200, 'cURL works with fopen disabled');
    } else {
        try {
            $factory->create('http://server:8080/echo');
            throw new \RuntimeException('Expected TransportNotAvailable.');
        } catch (TransportNotAvailable $exception) {
            $check(str_contains($exception->getMessage(), 'fopen'), 'exception explains disabled stream functions');
        }
    }
    exit;
}
if (in_array('--disabled-curl', $argv, true)) {
    $check(!function_exists('curl_exec'), 'cURL execution function is disabled');
    $check($factory->create('http://server:8080/echo')::class === HTTPTransport::class, 'disabled cURL uses streams');
    $result = (new HTTPClient('http://server:8080/echo'))->execute();
    $check($result->getInfo()->httpCode === 200, 'stream fallback sends a real request');
    exit;
}
$expected = $withCurl ? CurlTransport::class : HTTPSTransport::class;
$check($factory->create('https://server:8443/echo')::class === $expected, 'factory selects the expected backend');

$strictSSL = getenv('TWPG_TEST_STRICT_SSL') === '1';
echo 'Testing strictSSL=' . ($strictSSL ? 'true' : 'false') . PHP_EOL;
$xml = '<Request>€</Request>';
$trust = ['ca' => ['file' => '/certs/ca.crt']];
$certificate = $trust + ['cert' => '/certs/client.crt', 'key' => '/certs/client.key', 'keyPass' => 'integration-only'];
$create = static function (
    string $class,
    string $url,
    ?string $body = null,
    ?array $ssl = null,
    bool $strict = false,
    ?string $userAgent = null
): object {
    return $class === HTTPTransport::class
        ? new HTTPTransport($url, $body, $userAgent) : new $class($url, $body, $ssl, $strict, $userAgent);
};
$secureClasses = [HTTPSTransport::class, HTTPClient::class];
$plainClasses = [HTTPTransport::class, HTTPClient::class];
if ($withCurl) {
    $secureClasses[] = CurlTransport::class;
    $plainClasses[] = CurlTransport::class;
}
foreach (['http://server:8080' => $plainClasses, 'https://server:8443' => $secureClasses] as $base => $classes) {
    foreach ($classes as $class) {
        $label = $class . ' ' . $base;
        $result = ($create($class, $base . '/echo', $xml, $trust, $strictSSL))->execute();
        $check($result->getInfo()->httpCode === 200, $label . ' receives HTTP 200');
        $echo = json_decode($result->getOutput(), true, 512, JSON_THROW_ON_ERROR);
        $check($echo['method'] === 'POST' && $echo['content'] === $xml, 'XML POST bytes reach the server');
        $check($echo['content_type'] === 'application/xml', 'XML content type reaches the server');
        $check($echo['content_length'] === (string)strlen($xml), 'UTF-8 content length is measured in bytes');
        $expectedAgent = 'OpenPaymentSolutions/TranzWarePaymentGateway/v' . trim(file_get_contents('/app/VERSION'));
        $check($echo['user_agent'] === $expectedAgent, $label . ' sends the versioned default User-Agent');
        $custom = $create($class, $base . '/echo', $xml, $trust, $strictSSL, 'IntegrationClient/2.0');
        $customEcho = json_decode($custom->execute()->getOutput(), true, 512, JSON_THROW_ON_ERROR);
        $check($customEcho['user_agent'] === 'IntegrationClient/2.0', $label . ' sends a custom User-Agent');
        $check($result->getInfo()->contentType === 'application/json', 'response content type is exposed');
        $check($result->getInfo()->totalTime > 0, 'elapsed time is measured');

        $result = ($create($class, $base . '/status/503', '', $trust, $strictSSL))->execute();
        $check(
            $result->getInfo()->httpCode === 503 && $result->getOutput() === '<Error/>',
            'HTTP errors preserve body'
        );
        $result = ($create($class, $base . '/redirect', '', $trust, $strictSSL))->execute();
        $check(
            $result->getInfo()->httpCode === 302 && $result->getOutput() === '<Redirect/>',
            'redirects are not followed'
        );
        $result = ($create($class, $base . '/empty', null, $trust, $strictSSL))->execute();
        $check($result->getInfo()->httpCode === 204 && $result->getOutput() === '', 'empty response remains a string');
        $result = ($create($class, $base . '/interim', null, $trust, $strictSSL))->execute();
        $check($result->getInfo()->httpCode === 200, 'final status follows interim response');
        $check($result->getInfo()->contentType === 'application/json', 'final content type follows interim response');
        $result = ($create($class, $base . '/chunked', null, $trust, $strictSSL))->execute();
        $check($result->getOutput() === '<Response/>', 'chunked responses are decoded');
        $result = ($create($class, $base . '/truncated', null, $trust, $strictSSL))->execute();
        $check($result->getOutput() === false && $result->getInfo()->httpCode === 200, 'truncated responses fail');
        $unavailable = str_starts_with($base, 'https:') ? 'https://server:1' : 'http://server:1';
        $result = ($create($class, $unavailable, null, $trust, $strictSSL))->execute();
        $check($result->getOutput() === false && $result->getInfo()->httpCode === 0, 'connection refusal is a failure');
        $check($result->getInfo()->__unparsed['error'] !== null, 'connection failure includes diagnostics');
    }
}

foreach ($secureClasses as $class) {
    $untrustedPeers = [['https://server:8443/echo', null], ['https://wrong-server:8443/echo', $trust]];
    foreach ($untrustedPeers as [$url, $ssl]) {
        $result = (new $class($url, null, $ssl, $strictSSL))->execute();
        $check(
            $strictSSL ? $result->getOutput() === false : $result->getInfo()->httpCode === 200,
            $class . ' applies the selected strictSSL mode to certificate and hostname checks'
        );
    }

    $rejectedClients = [
        new $class('https://server:8443/echo', null, null, true),
        new $class('https://wrong-server:8443/echo', null, $trust, true),
        new $class('https://server:8444/echo', null, $trust, $strictSSL),
        new $class('https://server:8444/echo', null, $trust + [
            'cert' => '/certs/untrusted.crt', 'key' => '/certs/untrusted.key',
        ], $strictSSL),
        new $class('https://server:8444/echo', null, array_replace($certificate, ['keyPass' => 'wrong']), $strictSSL),
    ];
    foreach ($rejectedClients as $index => $client) {
        $result = $client->execute();
        $check(
            $result->getOutput() === false && $result->getInfo()->httpCode === 0,
            $class . ' TLS rejection ' . $index
        );
        $check($result->getInfo()->__unparsed['error'] !== null, 'TLS failure diagnostics are available');
    }
    $result = (new $class('https://server:8444/echo', $xml, $certificate, $strictSSL))->execute();
    $check($result->getInfo()->httpCode === 200, $class . ' mutual TLS with encrypted client key succeeds');
    $echo = json_decode($result->getOutput(), true, 512, JSON_THROW_ON_ERROR);
    $check($echo['client_certificate'] === true, 'server verifies the client certificate');
    $check($echo['content'] === $xml, 'XML request survives mutual TLS');
    $result = (new $class('https://wrong-server:8443/echo'))->execute();
    $check($result->getInfo()->httpCode === 200, $class . ' defaults strictSSL to false');
}

$client = new class ('http://server:8080/timeout') extends HTTPTransport {
    /**
     * Shorten the real read timeout for the integration fixture.
     *
     * @return array
     */
    protected function contextOptions(): array
    {
        $options = parent::contextOptions();
        $options['http']['timeout'] = 0.2;
        return $options;
    }
};
$result = $client->execute();
$check($result->getOutput() === false && $result->getInfo()->__unparsed['timed_out'], 'partial response times out');
echo 'All standalone Docker transport checks passed.' . PHP_EOL;
