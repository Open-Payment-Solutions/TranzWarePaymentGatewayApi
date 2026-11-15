<?php

namespace tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use OpenPaymentSolutions\TranzWarePaymentGateway\Data\OrderCallbackData;
use OpenPaymentSolutions\TranzWarePaymentGateway\Data\OrderStatusData;

final class SamplesTest extends TestCase
{
    /**
     * Run a sample script with simulated transport and collect its response.
     *
     * @param string $sample
     * @param array $scenario
     *
     * @return array
     */
    private function runSample(string $sample, array $scenario): array
    {
        $process = proc_open([
            PHP_SAPI === 'phpdbg' ? PHP_BINDIR . '/php' : PHP_BINARY,
            __DIR__ . '/fixtures/run_sample.php',
            $sample,
            json_encode($scenario, JSON_THROW_ON_ERROR),
        ], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $this->assertIsResource($process);
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);
        $this->assertSame('', $errors);
        $this->assertSame(0, $exitCode);
        return json_decode($output, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Build an order creation response for the sample tests.
     *
     * @param string $status
     *
     * @return string
     */
    private static function orderResponse(string $status = '00'): string
    {
        return '<TKKPG><Response><Status>' . $status . '</Status><Order>'
            . '<URL>https://gateway.example/pay</URL><OrderID>123</OrderID>'
            . '<SessionID>session</SessionID></Order></Response></TKKPG>';
    }

    /**
     * Display creation exceptions with an HTTP error status.
     *
     * @param array $scenario
     * @param string $message
     *
     * @see    self::rejectedOrders()
     * @return void
     */
    #[DataProvider('rejectedOrders')]
    public function testCreateSampleHandlesThrownFailures(array $scenario, string $message): void
    {
        $result = $this->runSample('create.php', $scenario);
        $this->assertSame(502, $result['status']);
        $this->assertSame($message, $result['output']);
        $this->assertStringNotContainsString('LINK TO PAYMENT', $result['output']);
        $this->assertStringNotContainsString('FAILED', $result['output']);
    }

    /**
     * Provide rejected and malformed order creation responses.
     *
     * @return array
     */
    public static function rejectedOrders(): array
    {
        return [
            'gateway rejects creation' => [
                ['body' => self::orderResponse('30'), 'http_code' => 200],
                'Gateway rejected order creation.',
            ],
            'unsuccessful HTTP status' => [
                ['body' => self::orderResponse(), 'http_code' => 500],
                'Gateway rejected order creation.',
            ],
            'transport failure' => [['body' => false, 'http_code' => 0], 'Gateway rejected order creation.'],
            'malformed XML' => [['body' => 'invalid XML', 'http_code' => 200], 'Invalid gateway response'],
            'missing payment identifiers' => [
                ['body' => '<TKKPG><Response><Status>00</Status><Order/></Response></TKKPG>', 'http_code' => 200],
                'Incomplete gateway order response',
            ],
        ];
    }

    /**
     * Display the escaped payment link after successful order creation.
     *
     * @return void
     */
    public function testCreateSampleStillPrintsPaymentLinkOnSuccess(): void
    {
        $result = $this->runSample('create.php', ['body' => self::orderResponse(), 'http_code' => 200]);
        $this->assertSame(200, $result['status']);
        $this->assertSame(
            '<a href="https://gateway.example/pay?ORDERID=123&amp;SESSIONID=session">LINK TO PAYMENT</a>',
            $result['output']
        );
    }

    /**
     * Display callback exception messages.
     *
     * @param string $sample
     * @param array $request
     * @param string $message
     *
     * @see    self::invalidCallbacks()
     * @return void
     */
    #[DataProvider('invalidCallbacks')]
    public function testCallbackSamplesPrintExceptionMessages(string $sample, array $request, string $message): void
    {
        $result = $this->runSample($sample, ['request' => $request]);
        $this->assertSame(400, $result['status']);
        $this->assertSame($message, $result['output']);
        $this->assertStringNotContainsString('array(', $result['output']);
    }

    /**
     * Provide invalid callback inputs for each callback sample.
     *
     * @return array
     */
    public static function invalidCallbacks(): array
    {
        $rows = [];
        foreach (['order_approved.php', 'order_declined.php', 'order_canceled.php'] as $sample) {
            $rows[] = [$sample, [], 'Empty XML message'];
            $rows[] = [$sample, ['xmlmsg' => ''], 'Empty XML message'];
            $rows[] = [$sample, ['xmlmsg' => 'invalid XML'], 'Invalid XML message'];
            $rows[] = [$sample, ['xmlmsg' => '<Order><OrderID>123</OrderID></Order>'], 'Invalid XML message'];
        }
        return $rows;
    }

    /**
     * Display parsed callback data on success.
     *
     * @param string $sample
     * @param string $message
     *
     * @see    self::validCallbacks()
     * @return void
     */
    #[DataProvider('validCallbacks')]
    public function testCallbackSamplesDumpParsedDataOnSuccess(string $sample, string $message): void
    {
        $result = $this->runSample($sample, ['request' => ['xmlmsg' => $message]]);
        $this->assertSame(200, $result['status']);
        $this->assertStringContainsString('object(' . OrderCallbackData::class . ')', $result['output']);
        $this->assertStringContainsString('"orderId"', $result['output']);
        $this->assertStringContainsString('"123"', $result['output']);
        $this->assertStringContainsString('"APPROVED"', $result['output']);
    }

    /**
     * Provide raw XML and base64 inputs for each callback sample.
     *
     * @return array
     */
    public static function validCallbacks(): array
    {
        $xml = '<Order><TranDateTime>2026-11-15</TranDateTime><OrderID>123</OrderID>'
            . '<PurchaseAmount>100</PurchaseAmount><Currency>840</Currency><OrderStatus>APPROVED</OrderStatus></Order>';
        $rows = [];
        foreach (['order_approved.php', 'order_declined.php', 'order_canceled.php'] as $sample) {
            $rows[] = [$sample, $xml];
            $rows[] = [$sample, base64_encode($xml)];
        }
        return $rows;
    }

    /**
     * Display retrieved order status data.
     *
     * @param string $status
     *
     * @see    self::retrievedStatuses()
     * @return void
     */
    #[DataProvider('retrievedStatuses')]
    public function testStatusSampleDumpsRetrievedOutcome(string $status): void
    {
        $body = str_replace('</Order>', '<OrderStatus>' . $status . '</OrderStatus></Order>', self::orderResponse());
        $result = $this->runSample('get_status.php', [
            'get' => ['ORDERID' => '123', 'SESSIONID' => 'session'],
            'body' => $body,
            'http_code' => 200,
        ]);
        $this->assertSame(200, $result['status']);
        $this->assertStringContainsString('object(' . OrderStatusData::class . ')', $result['output']);
        $this->assertStringContainsString('"' . $status . '"', $result['output']);
    }

    /**
     * Provide approved and declined statuses for the status sample.
     *
     * @return array
     */
    public static function retrievedStatuses(): array
    {
        return [['APPROVED'], ['DECLINED']];
    }

    /**
     * Distinguish validation errors from retrieval failures.
     *
     * @param array $scenario
     * @param int $status
     * @param string $message
     *
     * @see    self::failedStatusRequests()
     * @return void
     */
    #[DataProvider('failedStatusRequests')]
    public function testStatusSampleDistinguishesValidationFromRetrievalFailures(
        array $scenario,
        int $status,
        string $message,
    ): void {
        $result = $this->runSample('get_status.php', $scenario);
        $this->assertSame($status, $result['status']);
        $this->assertSame($message, $result['output']);
        $this->assertStringNotContainsString('array(', $result['output']);
    }

    /**
     * Provide invalid identifiers and failed responses for the status sample.
     *
     * @return array
     */
    public static function failedStatusRequests(): array
    {
        return [
            [[], 400, 'Invalid order ID'],
            [['get' => ['ORDERID' => '123']], 400, 'Invalid session ID'],
            [['get' => ['ORDERID' => '', 'SESSIONID' => 'session']], 400, 'Invalid order ID'],
            [['get' => ['ORDERID' => '123', 'SESSIONID' => ' ']], 400, 'Invalid session ID'],
            [
                ['get' => ['ORDERID' => '123', 'SESSIONID' => 'session'], 'body' => false],
                502,
                'Unable to retrieve order status.',
            ],
            [
                [
                    'get' => ['ORDERID' => '123', 'SESSIONID' => 'session'],
                    'body' => 'invalid XML',
                    'http_code' => 200,
                ],
                502,
                'Unable to retrieve order status.',
            ],
        ];
    }
}
