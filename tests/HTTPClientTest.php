<?php

namespace tests;

use OpenPaymentSolutions\TranzWarePaymentGateway\Requests\HTTPClient;
use OpenPaymentSolutions\TranzWarePaymentGateway\Requests\HTTPClientInterface;
use OpenPaymentSolutions\TranzWarePaymentGateway\Requests\HTTPClientResult;
use PHPUnit\Framework\TestCase;
use tests\fixtures\Transport;

final class HTTPClientTest extends TestCase
{
    protected function setUp(): void
    {
        Transport::$enabled = true;
        Transport::$options = [];
        Transport::$output = '<Response/>';
        Transport::$info = ['http_code' => 200];
    }

    protected function tearDown(): void
    {
        Transport::$enabled = false;
        Transport::$options = [];
    }

    public function testPostsXmlAndReturnsUnmodifiedTransportResult(): void
    {
        $client = new HTTPClient('https://gateway.example', '<Request>€</Request>');
        $this->assertInstanceOf(HTTPClientInterface::class, $client);
        $result = $client->execute();
        $this->assertSame('<Response/>', $result->getOutput());
        $this->assertSame(['http_code' => 200], $result->getInfo());
        $options = Transport::$options;
        $this->assertSame('https://gateway.example', $options[CURLOPT_URL]);
        $this->assertSame(10, $options[CURLOPT_CONNECTTIMEOUT]);
        $this->assertTrue($options[CURLOPT_POST]);
        $this->assertTrue($options[CURLOPT_RETURNTRANSFER]);
        $this->assertFalse($options[CURLOPT_HEADER]);
        $this->assertFalse($options[CURLOPT_VERBOSE]);
        $this->assertSame('<Request>€</Request>', $options[CURLOPT_POSTFIELDS]);
        $this->assertSame(['Content-Type: application/xml', 'Content-Length: 22'], $options[CURLOPT_HTTPHEADER]);
    }

    /** @dataProvider sslModes */
    public function testCertificateAndVerificationOptions(bool $strict): void
    {
        $ssl = ['cert' => '/client.crt', 'key' => '/client.key', 'keyPass' => 'test-pass',
            'ca' => ['path' => '/ca', 'file' => '/ca/root.crt']];
        (new HTTPClient('https://gateway.example', '', $ssl, $strict))->execute();
        $options = Transport::$options;
        $this->assertSame('/client.crt', $options[CURLOPT_SSLCERT]);
        $this->assertSame('/client.key', $options[CURLOPT_SSLKEY]);
        $this->assertSame('test-pass', $options[CURLOPT_SSLCERTPASSWD]);
        $this->assertSame('/ca', $options[CURLOPT_CAPATH]);
        $this->assertSame('/ca/root.crt', $options[CURLOPT_CAINFO]);
        $this->assertSame($strict ? 2 : 0, $options[CURLOPT_SSL_VERIFYHOST]);
        $this->assertSame($strict, $options[CURLOPT_SSL_VERIFYPEER]);
    }

    public static function sslModes(): array
    {
        return [[true], [false]];
    }

    public function testMinimalCertificateAndEmptyBody(): void
    {
        (new HTTPClient('https://gateway.example', null, ['cert' => '/cert', 'key' => '/key']))->execute();
        $this->assertArrayNotHasKey(CURLOPT_SSLCERTPASSWD, Transport::$options);
        $this->assertArrayNotHasKey(CURLOPT_CAINFO, Transport::$options);
        $this->assertSame(
            ['Content-Type: application/xml', 'Content-Length: 0'],
            Transport::$options[CURLOPT_HTTPHEADER]
        );
    }

    public function testTransportFailureIsPreserved(): void
    {
        Transport::$output = false;
        Transport::$info = ['http_code' => 0];
        $result = (new HTTPClient('https://gateway.example'))->execute();
        $this->assertFalse($result->getOutput());
        $this->assertSame(['http_code' => 0], $result->getInfo());
    }

    public function testDebugOutputIsWrittenAndDescriptorsAreClosed(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'twpg-test-');
        try {
            $client = new HTTPClient('https://gateway.example', '<Request/>');
            $client->setDebugToFile($file);
            $client->setDebugToFile($file);
            $client->execute();
            $descriptor = Transport::$options[CURLOPT_STDERR];
            $this->assertTrue(Transport::$options[CURLOPT_VERBOSE]);
            $log = file_get_contents($file);
            foreach (['https://gateway.example', '<Request/>', 'http_code', '<Response/>'] as $text) {
                $this->assertStringContainsString($text, $log);
            }
            unset($client);
            $this->assertFalse(is_resource($descriptor));
        } finally {
            unlink($file);
        }
    }

    public function testUnwritableDebugPathThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        (new HTTPClient('https://gateway.example'))->setDebugToFile('/nonexistent-twpg-directory/debug.log');
    }

    public function testRawResultKeepsEmptyOutputAndInfo(): void
    {
        $result = new HTTPClientResult('', []);
        $this->assertSame('', $result->getOutput());
        $this->assertSame([], $result->getInfo());
    }
}
