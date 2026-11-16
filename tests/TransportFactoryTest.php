<?php

namespace tests;

use OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions\TransportNotAvailable;
use OpenPaymentSolutions\TranzWarePaymentGateway\Requests\HTTPClient;
use OpenPaymentSolutions\TranzWarePaymentGateway\TransportFactory;
use OpenPaymentSolutions\TranzWarePaymentGateway\Transports\CurlTransport;
use OpenPaymentSolutions\TranzWarePaymentGateway\Transports\HTTPTransport;
use OpenPaymentSolutions\TranzWarePaymentGateway\Transports\HTTPSTransport;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use tests\fixtures\Transport;
use tests\fixtures\TransportCapabilities as Capabilities;

final class TransportFactoryTest extends TestCase
{
    /**
     * Simulate available streams and optional cURL without using the network.
     *
     * @return void
     */
    protected function setUp(): void
    {
        Transport::$enabled = true;
        Transport::$output = '<Response/>';
        Transport::$info = ['http_code' => 200];
        Capabilities::$extensions = ['curl' => false, 'openssl' => true];
        Capabilities::$functions = array_fill_keys([
            'curl_init', 'curl_setopt_array', 'curl_exec', 'curl_getinfo', 'curl_error', 'curl_version',
        ], true);
        Capabilities::$settings = ['allow_url_fopen' => '1'];
        Capabilities::$wrappers = ['http', 'https'];
        Capabilities::$transports = ['tcp', 'ssl'];
        Capabilities::$curlProtocols = ['http', 'https'];
    }

    /**
     * Restore real runtime capability checks after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Transport::$enabled = false;
        Capabilities::$extensions = [];
        Capabilities::$functions = [];
        Capabilities::$settings = [];
        Capabilities::$wrappers = null;
        Capabilities::$transports = null;
        Capabilities::$curlProtocols = null;
    }

    /**
     * Prefer cURL even when PHP streams and the PHP OpenSSL extension are disabled.
     *
     * @return void
     */
    public function testCurlIsPreferredWithoutDependingOnStreamRequirements(): void
    {
        Capabilities::$extensions = ['curl' => true, 'openssl' => false];
        Capabilities::$settings['allow_url_fopen'] = '0';
        Capabilities::$wrappers = [];
        Capabilities::$transports = [];
        foreach (['http', 'https'] as $scheme) {
            $transport = (new TransportFactory())->create($scheme . '://gateway.example');
            $this->assertInstanceOf(CurlTransport::class, $transport);
        }
    }

    /**
     * Fall back when cURL is absent, disabled, or lacks protocol support.
     *
     * @param string $reason
     * @param string $scheme
     * @param string $expected
     *
     * @see    self::fallbackModes()
     * @return void
     */
    #[DataProvider('fallbackModes')]
    public function testStreamFallback(string $reason, string $scheme, string $expected): void
    {
        Capabilities::$extensions['curl'] = $reason !== 'missing extension';
        if ($reason === 'disabled function') {
            Capabilities::$functions['curl_exec'] = false;
        } elseif ($reason === 'missing protocol') {
            Capabilities::$curlProtocols = [];
        }
        $transport = (new TransportFactory())->create($scheme . '://gateway.example');
        $this->assertSame($expected, $transport::class);
    }

    /**
     * Provide HTTP and HTTPS fallback cases.
     *
     * @return array
     */
    public static function fallbackModes(): array
    {
        return [['missing extension', 'http', HTTPTransport::class],
            ['missing extension', 'https', HTTPSTransport::class],
            ['disabled function', 'https', HTTPSTransport::class],
            ['missing protocol', 'https', HTTPSTransport::class]];
    }

    /**
     * Allow HTTP streams without OpenSSL or an SSL stream transport.
     *
     * @return void
     */
    public function testPlainHttpDoesNotRequireTls(): void
    {
        Capabilities::$extensions['openssl'] = false;
        Capabilities::$wrappers = ['http'];
        Capabilities::$transports = ['tcp'];
        $this->assertSame(HTTPTransport::class, (new TransportFactory())->create('http://gateway.example')::class);
    }

    /**
     * Preserve body, certificates, and both explicit and default TLS settings.
     *
     * @return void
     */
    public function testFactoryPreservesRequestSettings(): void
    {
        $factory = new TransportFactory();
        $ssl = ['cert' => '/client.crt', 'key' => '/client.key', 'keyPass' => 'test-only'];
        $factory->create('https://gateway.example', '<Request/>', $ssl)->execute();
        $this->assertSame('<Request/>', Transport::$options['http']['content']);
        $this->assertSame('/client.crt', Transport::$options['ssl']['local_cert']);
        $this->assertSame('/client.key', Transport::$options['ssl']['local_pk']);
        $this->assertSame('test-only', Transport::$options['ssl']['passphrase']);
        $this->assertFalse(Transport::$options['ssl']['verify_peer']);
        $factory->create('https://gateway.example', null, $ssl, true)->execute();
        $this->assertTrue(Transport::$options['ssl']['verify_peer']);
        $this->assertTrue(Transport::$options['ssl']['verify_peer_name']);
    }

    /**
     * Explain each missing requirement with a dedicated exception before requests.
     *
     * @param string $requirement
     * @param string $message
     *
     * @see    self::missingRequirements()
     * @return void
     */
    #[DataProvider('missingRequirements')]
    public function testUnavailableTransportExplainsWhy(string $requirement, string $message): void
    {
        match ($requirement) {
            'setting' => Capabilities::$settings['allow_url_fopen'] = '0',
            'function' => Capabilities::$functions['fopen'] = false,
            'wrapper' => Capabilities::$wrappers = ['http'],
            'tcp' => Capabilities::$transports = ['ssl'],
            'tls' => Capabilities::$transports = ['tcp'],
            'openssl' => Capabilities::$extensions['openssl'] = false,
        };
        $this->expectException(TransportNotAvailable::class);
        $this->expectExceptionMessage($message);
        (new HTTPClient('https://gateway.example'))->execute();
    }

    /**
     * Provide stream requirements and their diagnostic messages.
     *
     * @return array
     */
    public static function missingRequirements(): array
    {
        return [['setting', 'allow_url_fopen is disabled'], ['function', 'fopen'],
            ['wrapper', 'https URL wrapper is not registered'], ['tcp', 'TCP stream transport is not registered'],
            ['tls', 'SSL stream transport is not registered'], ['openssl', 'OpenSSL extension is not loaded']];
    }

    /**
     * Include cURL and stream failures in one actionable diagnostic.
     *
     * @return void
     */
    public function testAllUnavailableBackendsAreExplained(): void
    {
        Capabilities::$extensions['curl'] = true;
        Capabilities::$functions['curl_exec'] = false;
        Capabilities::$settings['allow_url_fopen'] = '0';
        try {
            (new TransportFactory())->create('https://gateway.example');
            $this->fail('Expected no available transport.');
        } catch (TransportNotAvailable $exception) {
            $this->assertStringContainsString('curl_exec', $exception->getMessage());
            $this->assertStringContainsString('allow_url_fopen is disabled', $exception->getMessage());
        }
    }
}
