<?php

namespace tests;

use OpenPaymentSolutions\TranzWarePaymentGateway\Requests\HTTPClient;
use OpenPaymentSolutions\TranzWarePaymentGateway\Transports\HTTPTransport;
use OpenPaymentSolutions\TranzWarePaymentGateway\Transports\HTTPSTransport;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use tests\fixtures\Transport;

final class TransportsTest extends TestCase
{
    /**
     * Prepare in-memory stream responses without network requests.
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
     * Disable stream simulation after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Transport::$enabled = false;
    }

    /**
     * Select plain HTTP without TLS options.
     *
     * @return void
     */
    public function testHttpClientSelectsPlainTransport(): void
    {
        $result = (new HTTPClient('http://gateway.example', '<Request/>'))->execute();
        $this->assertSame('<Response/>', $result->getOutput());
        $this->assertArrayNotHasKey('ssl', Transport::$options);
        $this->assertSame('http://gateway.example', $result->getInfo()->url);
        $this->assertGreaterThanOrEqual(0, $result->getInfo()->totalTime);
    }

    /**
     * Apply TLS verification settings even without client certificates.
     *
     * @return void
     */
    public function testHttpsVerificationCanBeExplicitlyDisabledWithoutCertificate(): void
    {
        (new HTTPSTransport('https://gateway.example', null, null, false))->execute();
        $this->assertFalse(Transport::$options['ssl']['verify_peer']);
        $this->assertFalse(Transport::$options['ssl']['verify_peer_name']);
        $this->assertTrue(Transport::$options['ssl']['allow_self_signed']);
    }

    /**
     * Preserve HTTP bodies, final status, and content type after interim responses.
     *
     * @param int $status
     *
     * @see    self::httpStatuses()
     * @return void
     */
    #[DataProvider('httpStatuses')]
    public function testHttpStatusAndFinalHeadersArePreserved(int $status): void
    {
        Transport::$info['response_headers'] = [
            'HTTP/1.1 100 Continue', 'Content-Type: interim/type',
            'HTTP/1.1 ' . $status . ' Test', 'cOnTeNt-TyPe: application/xml; charset=UTF-8',
        ];
        $result = (new HTTPTransport('http://gateway.example'))->execute();
        $this->assertSame('<Response/>', $result->getOutput());
        $this->assertSame($status, $result->getInfo()->httpCode);
        $this->assertSame('application/xml; charset=UTF-8', $result->getInfo()->contentType);
        $this->assertSame(Transport::$info['response_headers'], $result->getInfo()->__unparsed['response_headers']);
    }

    /**
     * Provide success, redirect, and failure statuses.
     *
     * @return array
     */
    public static function httpStatuses(): array
    {
        return [[200], [302], [400], [503]];
    }

    /**
     * Reject partial bodies returned after a stream read timeout.
     *
     * @return void
     */
    public function testTimedOutPartialResponseIsTransportFailure(): void
    {
        Transport::$info['timed_out'] = true;
        $result = (new HTTPTransport('http://gateway.example'))->execute();
        $this->assertFalse($result->getOutput());
        $this->assertSame(200, $result->getInfo()->httpCode);
        $this->assertTrue($result->getInfo()->__unparsed['timed_out']);
        $this->assertFalse(is_resource(Transport::$stream));
    }

    /**
     * Reject truncated bodies instead of accepting a partial gateway response.
     *
     * @return void
     */
    public function testTruncatedResponseIsTransportFailure(): void
    {
        Transport::$info['response_headers'] = ['HTTP/1.1 200 OK', 'Content-Length: 100'];
        $result = (new HTTPTransport('http://gateway.example'))->execute();
        $this->assertFalse($result->getOutput());
        $this->assertSame('Incomplete HTTP response body.', $result->getInfo()->__unparsed['error']);
    }

    /**
     * Accept a complete response with leading zeros in its declared byte length.
     *
     * @return void
     */
    public function testContentLengthAllowsLeadingZeros(): void
    {
        Transport::$info['response_headers'] = ['HTTP/1.1 200 OK', 'Content-Length: 00011'];
        $result = (new HTTPTransport('http://gateway.example'))->execute();
        $this->assertSame('<Response/>', $result->getOutput());
        $this->assertNull($result->getInfo()->__unparsed['error']);
    }

    /**
     * Discard content types attached to interim responses.
     *
     * @return void
     */
    public function testInterimContentTypeDoesNotLeakIntoFinalResponse(): void
    {
        Transport::$info['response_headers'] = ['HTTP/1.1 100 Continue', 'Content-Type: interim/type',
            'HTTP/1.1 200 OK'];
        $result = (new HTTPTransport('http://gateway.example'))->execute();
        $this->assertSame(200, $result->getInfo()->httpCode);
        $this->assertNull($result->getInfo()->contentType);
    }

    /**
     * Restore the application's warning handler after stream operations.
     *
     * @return void
     */
    public function testApplicationErrorHandlerIsRestored(): void
    {
        $handler = static fn (): bool => false;
        set_error_handler($handler);
        try {
            (new HTTPTransport('http://gateway.example'))->execute();
            $previous = set_error_handler($handler);
            restore_error_handler();
            $this->assertSame($handler, $previous);
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Reject unsupported schemes and using the wrong transport for a URL.
     *
     * @param string $class
     * @param string $url
     *
     * @see    self::invalidUrls()
     * @return void
     */
    #[DataProvider('invalidUrls')]
    public function testInvalidUrlsAreRejectedBeforeOpeningStream(string $class, string $url): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new $class($url))->execute();
    }

    /**
     * Provide invalid endpoint and transport combinations.
     *
     * @return array
     */
    public static function invalidUrls(): array
    {
        return [[HTTPClient::class, 'file:///etc/passwd'], [HTTPClient::class, 'ftp://gateway.example'],
            [HTTPClient::class, '/relative'], [HTTPTransport::class, 'https://gateway.example'],
            [HTTPSTransport::class, 'http://gateway.example'], [HTTPTransport::class, 'http:///missing-host']];
    }
}
