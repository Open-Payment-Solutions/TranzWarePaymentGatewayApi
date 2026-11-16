<?php

namespace tests;

use OpenPaymentSolutions\TranzWarePaymentGateway\Requests\HTTPClient;
use OpenPaymentSolutions\TranzWarePaymentGateway\Requests\HTTPClientInterface;
use OpenPaymentSolutions\TranzWarePaymentGateway\Requests\HTTPClientResult;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use OpenPaymentSolutions\TranzWarePaymentGateway\Data\HTTPInfo;
use tests\fixtures\Transport;

use function OpenPaymentSolutions\TranzWarePaymentGateway\getUserAgent;

final class HTTPClientTest extends TestCase
{
    /**
     * Prepare the simulated HTTP transport.
     *
     * @return void
     */
    protected function setUp(): void
    {
        Transport::$enabled = true;
        Transport::$options = [];
        Transport::$output = '<Response/>';
        Transport::$info = ['http_code' => 200];
    }

    /**
     * Reset the simulated HTTP transport.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Transport::$enabled = false;
        Transport::$options = [];
    }

    /**
     * Post XML and preserve the raw transport result.
     *
     * @return void
     */
    public function testPostsXmlAndReturnsUnmodifiedTransportResult(): void
    {
        $client = new HTTPClient('https://gateway.example', '<Request>€</Request>');
        $this->assertInstanceOf(HTTPClientInterface::class, $client);
        $result = $client->execute();
        $this->assertSame('<Response/>', $result->getOutput());
        $this->assertInstanceOf(HTTPInfo::class, $result->getInfo());
        $this->assertSame(200, $result->getInfo()->httpCode);
        $this->assertFalse($result->getInfo()->__unparsed['timed_out']);
        $this->assertNull($result->getInfo()->__unparsed['error']);
        $options = Transport::$options;
        $this->assertSame('https://gateway.example', Transport::$url);
        $this->assertSame(10.0, $options['http']['timeout']);
        $this->assertSame('POST', $options['http']['method']);
        $this->assertSame(0, $options['http']['follow_location']);
        $this->assertTrue($options['http']['ignore_errors']);
        $this->assertSame('<Request>€</Request>', $options['http']['content']);
        $this->assertSame(
            ['Content-Type: application/xml', 'User-Agent: ' . getUserAgent(),
                'Content-Length: 22', 'Connection: close'],
            $options['http']['header']
        );
        $this->assertFalse($options['ssl']['verify_peer']);
        $this->assertFalse($options['ssl']['verify_peer_name']);
        $this->assertFalse(is_resource(Transport::$stream));
    }

    /**
     * Apply certificate and SSL verification settings.
     *
     * @param bool $strict
     *
     * @see    self::sslModes()
     * @return void
     */
    #[DataProvider('sslModes')]
    public function testCertificateAndVerificationOptions(bool $strict): void
    {
        $ssl = ['cert' => '/client.crt', 'key' => '/client.key', 'keyPass' => 'test-pass',
            'ca' => ['path' => '/ca', 'file' => '/ca/root.crt']];
        (new HTTPClient('https://gateway.example', '', $ssl, $strict))->execute();
        $options = Transport::$options['ssl'];
        $this->assertSame('/client.crt', $options['local_cert']);
        $this->assertSame('/client.key', $options['local_pk']);
        $this->assertSame('test-pass', $options['passphrase']);
        $this->assertSame('/ca', $options['capath']);
        $this->assertSame('/ca/root.crt', $options['cafile']);
        $this->assertSame($strict, $options['verify_peer_name']);
        $this->assertSame($strict, $options['verify_peer']);
    }

    /**
     * Provide enabled and disabled SSL verification modes.
     *
     * @return array
     */
    public static function sslModes(): array
    {
        return [[true], [false]];
    }

    /**
     * Allow an empty body with minimal certificate settings.
     *
     * @return void
     */
    public function testMinimalCertificateAndEmptyBody(): void
    {
        (new HTTPClient('https://gateway.example', null, ['cert' => '/cert', 'key' => '/key']))->execute();
        $this->assertArrayNotHasKey('passphrase', Transport::$options['ssl']);
        $this->assertArrayNotHasKey('cafile', Transport::$options['ssl']);
        $this->assertSame(
            ['Content-Type: application/xml', 'User-Agent: ' . getUserAgent(),
                'Content-Length: 0', 'Connection: close'],
            Transport::$options['http']['header']
        );
    }

    /**
     * Preserve failed transport output and HTTP status information.
     *
     * @return void
     */
    public function testTransportFailureIsPreserved(): void
    {
        Transport::$output = false;
        Transport::$info = ['http_code' => 0];
        $result = (new HTTPClient('https://gateway.example'))->execute();
        $this->assertFalse($result->getOutput());
        $this->assertSame(0, $result->getInfo()->httpCode);
    }

    /**
     * Write debug output and close replaced or unused file descriptors.
     *
     * @return void
     */
    public function testDebugOutputIsWrittenAndDescriptorsAreClosed(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'twpg-test-');
        try {
            $client = new HTTPClient('https://gateway.example', '<Request/>');
            $client->setDebugToFile($file);
            $property = new \ReflectionProperty($client, 'debugFileDescriptor');
            $oldDescriptor = $property->getValue($client);
            $client->setDebugToFile($file);
            $this->assertFalse(is_resource($oldDescriptor));
            $client->execute();
            $descriptor = $property->getValue($client);
            $log = file_get_contents($file);
            foreach (['https://gateway.example', '<Request/>', 'httpCode', '<Response/>'] as $text) {
                $this->assertStringContainsString($text, $log);
            }
            unset($client);
            $this->assertFalse(is_resource($descriptor));
        } finally {
            unlink($file);
        }
    }

    /**
     * Reject an unwritable debug output path.
     *
     * @return void
     */
    public function testUnwritableDebugPathThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        (new HTTPClient('https://gateway.example'))->setDebugToFile('/nonexistent-twpg-directory/debug.log');
    }

    /**
     * Preserve empty output and transport information in raw results.
     *
     * @return void
     */
    public function testRawResultKeepsEmptyOutputAndInfo(): void
    {
        $result = new HTTPClientResult('', []);
        $this->assertSame('', $result->getOutput());
        $this->assertSame(0, $result->getInfo()->httpCode);
        $this->assertSame([], $result->getInfo()->__unparsed);
    }

    /**
     * Override the User-Agent through the constructor or setter and restore its default.
     *
     * @return void
     */
    public function testUserAgentCanBeChangedAndReset(): void
    {
        $client = new HTTPClient('https://gateway.example', null, null, false, 'CustomClient/1.0');
        $client->execute();
        $this->assertContains('User-Agent: CustomClient/1.0', Transport::$options['http']['header']);
        $client->setUserAgent('AnotherClient/2.0');
        $client->execute();
        $this->assertContains('User-Agent: AnotherClient/2.0', Transport::$options['http']['header']);
        $client->setUserAgent(null);
        $client->execute();
        $this->assertContains('User-Agent: ' . getUserAgent(), Transport::$options['http']['header']);
    }
}
